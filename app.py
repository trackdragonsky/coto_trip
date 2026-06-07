import os
import re
import uuid
from datetime import datetime, time, timedelta
from decimal import Decimal, InvalidOperation
from pathlib import Path
from zoneinfo import ZoneInfo

import requests
from dotenv import load_dotenv
from flask import Flask, jsonify, render_template, request
from flask_socketio import SocketIO
from groq import Groq
from werkzeug.utils import secure_filename

from db import execute, fetch_all, fetch_one, init_schema


load_dotenv()

BASE_DIR = Path(__file__).resolve().parent
UPLOAD_RELATIVE_DIR = Path("static") / "uploads" / "gallery"
UPLOAD_DIR = BASE_DIR / UPLOAD_RELATIVE_DIR
UPLOAD_DIR.mkdir(parents=True, exist_ok=True)

ALLOWED_IMAGE_EXTENSIONS = {".jpg", ".jpeg", ".png", ".webp", ".gif",".heic",".heif"}

app = Flask(__name__)
app.config["SECRET_KEY"] = os.getenv("SECRET_KEY", "coto-trip-secret")
app.config["MAX_CONTENT_LENGTH"] = 16 * 1024 * 1024

socketio = SocketIO(app, cors_allowed_origins="*",async_mode="threading")

WEATHER_API_KEY = os.getenv("WEATHER_API_KEY")
GROQ_API_KEY = os.getenv("GROQ_API_KEY")
GROQ_MODEL = os.getenv("GROQ_MODEL", "llama-3.1-8b-instant")
client = Groq(api_key=GROQ_API_KEY) if GROQ_API_KEY else None

init_schema()


# =========================
# Chuẩn hóa dữ liệu
# =========================

def asset_url(value):
    if not value:
        return ""

    path = str(value).replace("\\", "/")
    if path.startswith(("http://", "https://", "/")):
        return path

    return f"/{path}"


def serialize_datetime(value):
    if value is None:
        return ""

    if isinstance(value, datetime):
        return value.strftime("%Y-%m-%d %H:%M:%S")

    return str(value)


def serialize_date(value):
    if value is None:
        return ""

    if hasattr(value, "isoformat"):
        return value.isoformat()

    return str(value)[:10]


def serialize_time(value):
    if value is None:
        return ""

    if isinstance(value, time):
        return value.strftime("%H:%M")

    if isinstance(value, timedelta):
        total_seconds = int(value.total_seconds())
        hours = total_seconds // 3600
        minutes = (total_seconds % 3600) // 60
        return f"{hours:02d}:{minutes:02d}"

    return str(value)[:5]


def money_to_int(value):
    if value is None:
        return 0

    try:
        return int(Decimal(value).quantize(Decimal("1")))
    except (InvalidOperation, ValueError):
        return 0


def parse_amount(raw_value):
    clean_value = re.sub(r"[^\d]", "", str(raw_value or ""))
    if not clean_value:
        raise ValueError("Số tiền không hợp lệ")

    amount = Decimal(clean_value)
    if amount <= 0:
        raise ValueError("Số tiền phải lớn hơn 0")

    return amount


def normalize_bool(value, default=True):
    if value is None:
        return default

    return str(value).lower() not in {"0", "false", "off", "no"}


# =========================
# Truy vấn MySQL
# =========================

def get_members():
    rows = fetch_all(
        """
        SELECT id, name, avatar, role_name, created_at
        FROM members
        ORDER BY id ASC
        """
    )
    return [serialize_member(row) for row in rows]


def serialize_member(row):
    return {
        "id": int(row["id"]),
        "name": row["name"],
        "avatar": row.get("avatar") or "",
        "avatar_url": asset_url(row.get("avatar")),
        "role_name": row.get("role_name") or "Thành viên",
        "created_at": serialize_datetime(row.get("created_at")),
    }


def find_member(member_value):
    if not member_value:
        return None

    if str(member_value).isdigit():
        return fetch_one(
            """
            SELECT id, name, avatar, role_name, created_at
            FROM members
            WHERE id = %s
            """,
            (int(member_value),),
        )

    return fetch_one(
        """
        SELECT id, name, avatar, role_name, created_at
        FROM members
        WHERE name = %s
        """,
        (str(member_value).strip(),),
    )


def serialize_expense(row):
    payer_avatar = row.get("payer_avatar") or ""
    return {
        "id": int(row["id"]),
        "title": row["title"],
        "category": row.get("category") or row["title"],
        "note": row.get("note") or "",
        "payer_id": int(row["payer_id"]),
        "payer_name": row.get("payer_name") or "Không rõ",
        "payer_avatar": payer_avatar,
        "payer_avatar_url": asset_url(payer_avatar),
        "amount": money_to_int(row.get("amount")),
        "split_evenly": bool(row.get("split_evenly")),
        "created_at": serialize_datetime(row.get("created_at")),
    }


def get_expenses():
    rows = fetch_all(
        """
        SELECT
            e.id,
            e.title,
            e.category,
            e.note,
            e.payer_id,
            e.amount,
            e.split_evenly,
            e.created_at,
            m.name AS payer_name,
            m.avatar AS payer_avatar
        FROM expenses e
        LEFT JOIN members m ON e.payer_id = m.id
        ORDER BY e.created_at DESC, e.id DESC
        """
    )
    return [serialize_expense(row) for row in rows]

def get_collections():

    rows = fetch_all(
        """
        SELECT
            c.id,
            c.amount,
            c.collected_at,
            m.name AS member_name
        FROM collections c
        JOIN members m
            ON c.member_id = m.id
        ORDER BY c.collected_at DESC
        """
    )

    return [
        {
            "id": row["id"],
            "member_name": row["member_name"],
            "amount": int(row["amount"]),
            "collected_at": serialize_datetime(row["collected_at"])
        }
        for row in rows
    ]

def get_expense(expense_id):
    row = fetch_one(
        """
        SELECT
            e.id,
            e.title,
            e.category,
            e.note,
            e.payer_id,
            e.amount,
            e.split_evenly,
            e.created_at,
            m.name AS payer_name,
            m.avatar AS payer_avatar
        FROM expenses e
        LEFT JOIN members m ON e.payer_id = m.id
        WHERE e.id = %s
        """,
        (expense_id,),
    )
    return serialize_expense(row) if row else None


def calculate_expense_stats(expenses, members):
    total = sum(item["amount"] for item in expenses)
    shared_total = sum(item["amount"] for item in expenses if item["split_evenly"])
    member_count = len(members)
    per_person = round(shared_total / member_count) if member_count else 0
    paid_by_member = {member["id"]: 0 for member in members}

    for expense in expenses:
        if expense["payer_id"] in paid_by_member:
            paid_by_member[expense["payer_id"]] += expense["amount"]

    balances = []
    for member in members:
        paid = paid_by_member.get(member["id"], 0)
        balance = paid - per_person
        balances.append(
            {
                "member_id": member["id"],
                "member_name": member["name"],
                "avatar_url": member["avatar_url"],
                "paid": paid,
                "share": per_person,
                "balance": balance,
            }
        )

    debtors = [
        {"member_name": item["member_name"], "amount": abs(item["balance"])}
        for item in balances
        if item["balance"] < 0
    ]
    creditors = [
        {"member_name": item["member_name"], "amount": item["balance"]}
        for item in balances
        if item["balance"] > 0
    ]

    settlements = []
    debtor_index = 0
    creditor_index = 0

    while debtor_index < len(debtors) and creditor_index < len(creditors):
        debtor = debtors[debtor_index]
        creditor = creditors[creditor_index]
        amount = min(debtor["amount"], creditor["amount"])

        if amount > 0:
            settlements.append(
                {
                    "from": debtor["member_name"],
                    "to": creditor["member_name"],
                    "amount": amount,
                }
            )

        debtor["amount"] -= amount
        creditor["amount"] -= amount

        if debtor["amount"] <= 0:
            debtor_index += 1

        if creditor["amount"] <= 0:
            creditor_index += 1

    top_payer = max(balances, key=lambda item: item["paid"], default=None)

    return {
        "total": total,
        "shared_total": shared_total,
        "per_person": per_person,
        "balances": balances,
        "settlements": settlements,
        "top_payer": top_payer,
    }


def serialize_gallery_item(row):
    return {
        "id": int(row["id"]),
        "image_url": row["image_url"],
        "src": asset_url(row["image_url"]),
        "caption": row.get("caption") or "Khoảnh khắc chuyến đi",
        "uploaded_by": row.get("uploaded_by"),
        "uploader_name": row.get("uploader_name") or "Nhóm du lịch",
        "uploader_avatar_url": asset_url(row.get("uploader_avatar")),
        "created_at": serialize_datetime(row.get("created_at")),
    }


def get_gallery_items():
    rows = fetch_all(
        """
        SELECT
            g.id,
            g.image_url,
            g.caption,
            g.uploaded_by,
            g.created_at,
            m.name AS uploader_name,
            m.avatar AS uploader_avatar
        FROM gallery g
        LEFT JOIN members m ON g.uploaded_by = m.id
        ORDER BY g.created_at DESC, g.id DESC
        """
    )
    return [serialize_gallery_item(row) for row in rows]


def get_gallery_item(item_id):
    row = fetch_one(
        """
        SELECT
            g.id,
            g.image_url,
            g.caption,
            g.uploaded_by,
            g.created_at,
            m.name AS uploader_name,
            m.avatar AS uploader_avatar
        FROM gallery g
        LEFT JOIN members m ON g.uploaded_by = m.id
        WHERE g.id = %s
        """,
        (item_id,),
    )
    return serialize_gallery_item(row) if row else None


def serialize_itinerary(row):
    return {
        "id": int(row["id"]),
        "title": row["title"],
        "trip_date": serialize_date(row["trip_date"]),
        "trip_time": serialize_time(row["trip_time"]),
        "activity_type": row.get("activity_type") or "Hoạt động",
        "detail": row.get("detail") or "",
        "created_by": row.get("created_by"),
        "creator_name": row.get("creator_name") or "Nhóm du lịch",
        "created_at": serialize_datetime(row.get("created_at")),
    }


def get_itineraries():
    rows = fetch_all(
        """
        SELECT
            i.id,
            i.title,
            i.trip_date,
            i.trip_time,
            i.activity_type,
            i.detail,
            i.created_by,
            i.created_at,
            m.name AS creator_name
        FROM itineraries i
        LEFT JOIN members m ON i.created_by = m.id
        ORDER BY i.trip_date ASC, i.trip_time ASC, i.id ASC
        """
    )
    return [serialize_itinerary(row) for row in rows]


def get_itinerary(item_id):
    row = fetch_one(
        """
        SELECT
            i.id,
            i.title,
            i.trip_date,
            i.trip_time,
            i.activity_type,
            i.detail,
            i.created_by,
            i.created_at,
            m.name AS creator_name
        FROM itineraries i
        LEFT JOIN members m ON i.created_by = m.id
        WHERE i.id = %s
        """,
        (item_id,),
    )
    return serialize_itinerary(row) if row else None


def get_ai_messages(limit=80):
    rows = fetch_all(
        """
        SELECT id, sender, message, created_at
        FROM ai_messages
        ORDER BY created_at ASC, id ASC
        LIMIT %s
        """,
        (limit,),
    )

    return [
        {
            "id": int(row["id"]),
            "sender": row["sender"],
            "message": row["message"],
            "created_at": serialize_datetime(row.get("created_at")),
        }
        for row in rows
    ]


def save_ai_message(sender, message):
    return execute(
        """
        INSERT INTO ai_messages(sender, message)
        VALUES(%s, %s)
        """,
        (sender, message),
    )


# =========================
# Thời tiết
# =========================

def fallback_weather():
    current = {
        "weather": [{"main": "Clear", "description": "Trời đẹp"}],
        "main": {"temp": 29, "feels_like": 32, "humidity": 78},
        "wind": {"speed": 14},
        "name": "Cô Tô",
    }

    now = datetime.now(ZoneInfo("Asia/Ho_Chi_Minh"))
    forecast = {
        "list": [
            {
                "dt_txt": (now + timedelta(days=index)).strftime("%Y-%m-%d 12:00:00"),
                "main": {"temp": 28 + (index % 3)},
                "weather": [{"main": "Clear", "description": "Trời đẹp"}],
            }
            for index in range(1, 5)
        ]
    }
    return current, forecast


def get_weather():
    if not WEATHER_API_KEY:
        return fallback_weather()

    lat = 20.9747
    lon = 107.7665
    params = {
        "lat": lat,
        "lon": lon,
        "appid": WEATHER_API_KEY,
        "units": "metric",
        "lang": "vi",
    }

    try:
        current_response = requests.get(
            "https://api.openweathermap.org/data/2.5/weather",
            params=params,
            timeout=8,
        )
        forecast_response = requests.get(
            "https://api.openweathermap.org/data/2.5/forecast",
            params=params,
            timeout=8,
        )
        current_response.raise_for_status()
        forecast_response.raise_for_status()

        current = current_response.json()
        forecast_raw = forecast_response.json()

        if "weather" not in current or "main" not in current:
            return fallback_weather()

        wind_speed = current.get("wind", {}).get("speed", 0)
        current.setdefault("wind", {})["speed"] = round(float(wind_speed) * 3.6)

        vn_now = datetime.now(ZoneInfo("Asia/Ho_Chi_Minh")).date()
        added_dates = set()
        filtered_forecast = []

        for item in forecast_raw.get("list", []):
            dt_txt = item.get("dt_txt")
            if not dt_txt:
                continue

            item_datetime = datetime.strptime(dt_txt, "%Y-%m-%d %H:%M:%S")
            item_date = item_datetime.date()

            if item_date <= vn_now or item_date in added_dates:
                continue

            if item_datetime.hour not in {12, 15}:
                continue

            added_dates.add(item_date)
            filtered_forecast.append(
                {
                    "dt_txt": dt_txt,
                    "main": {"temp": round(item["main"]["temp"])},
                    "weather": [
                        {
                            "main": item["weather"][0]["main"],
                            "description": item["weather"][0]["description"],
                        }
                    ],
                }
            )

            if len(filtered_forecast) >= 4:
                break

        if not filtered_forecast:
            fallback_current, fallback_forecast = fallback_weather()
            return current or fallback_current, fallback_forecast

        return current, {"list": filtered_forecast}

    except Exception as error:
        print("Lỗi thời tiết:", error)
        return fallback_weather()


# =========================
# Trang chính
# =========================

@app.route("/")
def home():
    members = [
        {
            "name": "Long",
            "avatar": "/static/images/long.jpg"
        },
        {
            "name": "Hoa",
            "avatar": "/static/images/hoa.jpg"
        },
        {
            "name": "Linh",
            "avatar": "/static/images/linh.jpg"
        },
        {
            "name": "LAnh",
            "avatar": "/static/images/lanh.jpg"
        },
        {
            "name": "Lan",
            "avatar": "/static/images/lan.jpg"
        },
        {
            "name": "Bắc",
            "avatar": "/static/images/bac.jpg"
        }
    ]
    current, forecast = get_weather()
    weather_type = current.get("weather", [{}])[0].get("main", "Clear")

    gallery_items = get_gallery_items()
    itineraries = get_itineraries()

    return render_template(
        "index.html",
        members=members,
        current=current,
        forecast=forecast,
        weather_type=weather_type,
        gallery_items=gallery_items,
        gallery_count=len(gallery_items),
        upcoming_itineraries=itineraries[:2] 
    )


@app.route("/expenses")
def expenses():

    members = get_members()

    expense_rows = get_expenses()

    collections = get_collections()

    stats = calculate_expense_stats(
        expense_rows,
        members
    )

    total_collected = sum(
        item["amount"]
        for item in collections
    )

    return render_template(
        "expenses.html",
        members=members,
        expenses=expense_rows,
        stats=stats,
        total=stats["total"],
        collections=collections,
        total_collected=total_collected
    )

@app.route("/add_expense", methods=["POST"])
def add_expense():
    title = (request.form.get("title") or "").strip()
    category = (request.form.get("category") or title).strip()
    note = (request.form.get("note") or "").strip()
    payer_value = request.form.get("payer_id") or request.form.get("payer")
    split_evenly = normalize_bool(request.form.get("split_evenly"), default=True)

    if not title:
        return jsonify({"status": "error", "message": "Vui lòng nhập tên khoản chi."}), 400

    payer = find_member(payer_value)
    if not payer:
        return jsonify({"status": "error", "message": "Người chi không tồn tại."}), 400

    try:
        amount = parse_amount(request.form.get("amount"))
    except ValueError as error:
        return jsonify({"status": "error", "message": str(error)}), 400

    expense_id = execute(
        """
        INSERT INTO expenses(title, category, note, payer_id, amount, split_evenly)
        VALUES(%s, %s, %s, %s, %s, %s)
        """,
        (title, category, note, payer["id"], amount, int(split_evenly)),
    )
    expense = get_expense(expense_id)

    socketio.emit("expense_updated", expense)

    return jsonify({"status": "success", "expense": expense})
@app.route("/add_collection", methods=["POST"])
def add_collection():

    member_id = request.form.get("member_id")

    try:
        amount = parse_amount(
            request.form.get("amount")
        )
    except ValueError as e:
        return jsonify({
            "status":"error",
            "message":str(e)
        }),400

    execute(
        """
        INSERT INTO collections
        (
            member_id,
            amount
        )
        VALUES
        (
            %s,
            %s
        )
        """,
        (
            member_id,
            amount
        )
    )

    return jsonify({
        "status":"success"
    })
@app.route("/delete_expense/<int:expense_id>", methods=["DELETE"])
def delete_expense(expense_id):

    execute(
        "DELETE FROM expenses WHERE id=%s",
        (expense_id,)
    )

    socketio.emit(
        "expense_deleted",
        {"id": expense_id}
    )

    return jsonify({
        "status": "success"
    })
@app.route(
    "/delete_collection/<int:item_id>",
    methods=["DELETE"]
)
def delete_collection(item_id):

    execute(
        """
        DELETE FROM collections
        WHERE id=%s
        """,
        (item_id,)
    )

    return jsonify({
        "status":"success"
    })
@app.route("/gallery")
def gallery():
    return render_template(
        "gallery.html",
        gallery_items=get_gallery_items(),
        members=get_members(),
    )


@app.route("/upload_gallery", methods=["POST"])
def upload_gallery():
    uploaded_by_value = request.form.get("uploaded_by")
    uploader = find_member(uploaded_by_value) if uploaded_by_value else None
    caption = (request.form.get("caption") or "").strip()
    files = []

    for field_name in ("images", "image", "file"):
        files.extend(request.files.getlist(field_name))

    saved_items = []

    for file_storage in files:
        if not file_storage or not file_storage.filename:
            continue

        extension = Path(file_storage.filename or "").suffix.lower()

        if not extension:
            extension = ".jpg"
        if extension not in ALLOWED_IMAGE_EXTENSIONS:
            continue

        original_name = secure_filename(Path(file_storage.filename).stem) or "anh"
        file_name = f"{original_name}-{uuid.uuid4().hex[:12]}{extension}"
        file_path = UPLOAD_DIR / file_name
        try:
            file_storage.save(file_path)
        except Exception as error:
            print("Lỗi lưu ảnh:", error)
            continue

        relative_path = str(UPLOAD_RELATIVE_DIR / file_name).replace("\\", "/")
        item_caption = caption or Path(file_storage.filename).stem or "Khoảnh khắc chuyến đi"
        item_id = execute(
            """
            INSERT INTO gallery(image_url, caption, uploaded_by)
            VALUES(%s, %s, %s)
            """,
            (relative_path, item_caption, uploader["id"] if uploader else None),
        )
        item = get_gallery_item(item_id)
        if item:
            saved_items.append(item)

    if not saved_items:
        return jsonify({"status": "error", "message": "Không có ảnh hợp lệ để tải lên."}), 400

    socketio.emit("gallery_updated", saved_items)

    return jsonify({"status": "success", "items": saved_items})


@app.route("/map")
def map_page():
    members = [
        {
            "name":"Long",
            "avatar":"/static/images/long.jpg",
            "avatar_url":"/static/images/long.jpg"
        },
        {
            "name":"Hoa",
            "avatar":"/static/images/hoa.jpg",
            "avatar_url":"/static/images/hoa.jpg"
        },
        {
            "name":"Linh",
            "avatar":"/static/images/linh.jpg",
            "avatar_url":"/static/images/linh.jpg"
        },
        {
            "name":"LAnh",
            "avatar":"/static/images/lanh.jpg",
            "avatar_url":"/static/images/lanh.jpg"
        },
        {
            "name":"Lan",
            "avatar":"/static/images/lan.jpg",
            "avatar_url":"/static/images/lan.jpg"
        },
        {
            "name":"Bắc",
            "avatar":"/static/images/bac.jpg",
            "avatar_url":"/static/images/bac.jpg"
        }
    ]

    return render_template(
        "map.html",
        itineraries=get_itineraries(),
        members=members
    )

@app.route("/add_itinerary", methods=["POST"])
def add_itinerary():
    payload = request.get_json(silent=True) if request.is_json else request.form
    payload = payload or {}

    title = (payload.get("title") or "").strip()
    trip_date = (payload.get("trip_date") or payload.get("date") or "").strip()
    trip_time = (payload.get("trip_time") or payload.get("time") or "").strip()
    activity_type = (payload.get("activity_type") or payload.get("type") or "Hoạt động").strip()
    detail = (payload.get("detail") or "").strip()
    creator_value = payload.get("created_by")
    creator = find_member(creator_value) if creator_value else None

    if not title or not trip_date or not trip_time:
        return jsonify({"status": "error", "message": "Vui lòng nhập đầy đủ lịch trình."}), 400

    try:
        datetime.strptime(trip_date, "%Y-%m-%d")
        datetime.strptime(trip_time, "%H:%M")
    except ValueError:
        return jsonify({"status": "error", "message": "Ngày hoặc giờ chưa đúng định dạng."}), 400

    itinerary_id = execute(
        """
        INSERT INTO itineraries(title, trip_date, trip_time, activity_type, detail, created_by)
        VALUES(%s, %s, %s, %s, %s, %s)
        """,
        (title, trip_date, trip_time, activity_type, detail, creator["id"] if creator else None),
    )
    itinerary = get_itinerary(itinerary_id)

    socketio.emit("itinerary_updated", itinerary)

    return jsonify({"status": "success", "itinerary": itinerary})


@app.route("/update_itinerary/<int:item_id>", methods=["POST", "PUT"])
def update_itinerary(item_id):
    payload = request.get_json(silent=True) if request.is_json else request.form
    payload = payload or {}

    title = (payload.get("title") or "").strip()
    trip_date = (payload.get("trip_date") or payload.get("date") or "").strip()
    trip_time = (payload.get("trip_time") or payload.get("time") or "").strip()
    activity_type = (payload.get("activity_type") or payload.get("type") or "Hoạt động").strip()
    detail = (payload.get("detail") or "").strip()
    creator_value = payload.get("created_by")
    creator = find_member(creator_value) if creator_value else None

    if not title or not trip_date or not trip_time:
        return jsonify({"status": "error", "message": "Vui lòng nhập đầy đủ lịch trình."}), 400

    execute(
        """
        UPDATE itineraries
        SET title = %s,
            trip_date = %s,
            trip_time = %s,
            activity_type = %s,
            detail = %s,
            created_by = %s
        WHERE id = %s
        """,
        (title, trip_date, trip_time, activity_type, detail, creator["id"] if creator else None, item_id),
    )
    itinerary = get_itinerary(item_id)

    return jsonify({"status": "success", "itinerary": itinerary})


@app.route("/delete_itinerary/<int:item_id>", methods=["POST", "DELETE"])
def delete_itinerary(item_id):
    execute("DELETE FROM itineraries WHERE id = %s", (item_id,))
    return jsonify({"status": "success"})


@app.route("/ai")
def ai():
    return render_template("ai.html", ai_messages=get_ai_messages())


@app.route("/chatbot", methods=["POST"])
def chatbot():
    payload = request.get_json(silent=True) or {}
    message = (payload.get("message") or "").strip()

    if not message:
        return jsonify({"reply": "Bạn hãy nhập câu hỏi trước nhé."}), 400

    save_ai_message("user", message)

    if client is None:
        reply = "AI chưa được cấu hình GROQ_API_KEY."
        save_ai_message("assistant", reply)
        return jsonify({"reply": reply}), 503

    try:
        completion = client.chat.completions.create(
            model=GROQ_MODEL,
            messages=[
                {
                    "role": "system",
                    "content": (
                        "Bạn là trợ lý du lịch Cô Tô cao cấp. "
                        "Luôn trả lời bằng tiếng Việt thuần, ngắn gọn, thực tế, "
                        "ưu tiên lịch trình, chi phí, ăn uống, an toàn và trải nghiệm nhóm."
                    ),
                },
                {"role": "user", "content": message},
            ],
        )
        reply = completion.choices[0].message.content.strip()
        save_ai_message("assistant", reply)
        return jsonify({"reply": reply})

    except Exception as error:
        print("Lỗi AI:", error)
        reply = "AI đang bận, bạn thử lại sau nhé."
        save_ai_message("assistant", reply)
        return jsonify({"reply": reply}), 503

@app.route("/api/members")
def api_members():
    return jsonify(get_members())
@app.route("/api/expenses")
def api_expenses():
    expenses = get_expenses()
    members = get_members()

    return jsonify({
        "expenses": expenses,
        "stats": calculate_expense_stats(expenses, members)
    })
@app.route("/api/gallery")
def api_gallery():
    return jsonify(get_gallery_items())
@app.route("/api/itineraries")
def api_itineraries():
    return jsonify(get_itineraries())
@socketio.on("connect")
def connect():
    print("Người dùng đã kết nối")


if __name__ == "__main__":
    socketio.run(app, host="0.0.0.0", port=5000, debug=True)
