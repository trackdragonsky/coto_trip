import os
from contextlib import contextmanager

import pymysql
from dotenv import load_dotenv


load_dotenv()

DB_HOST = os.getenv("DB_HOST", "localhost")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_USER = os.getenv("DB_USER", "root")
DB_PASSWORD = os.getenv("DB_PASSWORD", "")
DB_NAME = os.getenv("DB_NAME", "coto_trip")


def _connection_config(database=None, autocommit=False):
    return {
        "host": DB_HOST,
        "port": DB_PORT,
        "user": DB_USER,
        "password": DB_PASSWORD,
        "database": database,
        "charset": "utf8mb4",
        "autocommit": autocommit,
        "cursorclass": pymysql.cursors.DictCursor,
    }


def ensure_database():
    with pymysql.connect(**_connection_config(database=None, autocommit=True)) as conn:
        with conn.cursor() as cursor:
            cursor.execute(
                f"""
                CREATE DATABASE IF NOT EXISTS `{DB_NAME}`
                CHARACTER SET utf8mb4
                COLLATE utf8mb4_unicode_ci
                """
            )


def get_connection(autocommit=False):
    return pymysql.connect(**_connection_config(database=DB_NAME, autocommit=autocommit))


@contextmanager
def db_cursor(commit=False):
    conn = get_connection()
    try:
        with conn.cursor() as cursor:
            yield cursor
        if commit:
            conn.commit()
    except Exception:
        if commit:
            conn.rollback()
        raise
    finally:
        conn.close()


def init_schema():
    ensure_database()

    statements = [
        """
        CREATE TABLE IF NOT EXISTS members (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            avatar VARCHAR(255) NULL,
            role_name VARCHAR(120) NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """,
        """
        CREATE TABLE IF NOT EXISTS expenses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(180) NOT NULL,
            category VARCHAR(120) NOT NULL,
            note TEXT NULL,
            payer_id INT NOT NULL,
            amount DECIMAL(14,2) NOT NULL,
            split_evenly TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_expenses_payer_id (payer_id),
            CONSTRAINT fk_expenses_payer
                FOREIGN KEY (payer_id) REFERENCES members(id)
                ON DELETE RESTRICT ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """,
        """
        CREATE TABLE IF NOT EXISTS collections (
            id INT AUTO_INCREMENT PRIMARY KEY,
            member_id INT NOT NULL,
            amount DECIMAL(14,2) NOT NULL,
            collected_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

            INDEX idx_collections_member (member_id),

            CONSTRAINT fk_collections_member
                FOREIGN KEY (member_id)
                REFERENCES members(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """,
        """
        CREATE TABLE IF NOT EXISTS gallery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            image_url VARCHAR(500) NOT NULL,
            caption VARCHAR(255) NULL,
            uploaded_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_gallery_uploaded_by (uploaded_by),
            CONSTRAINT fk_gallery_uploaded_by
                FOREIGN KEY (uploaded_by) REFERENCES members(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """,
        """
        CREATE TABLE IF NOT EXISTS itineraries (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(180) NOT NULL,
            trip_date DATE NOT NULL,
            trip_time TIME NOT NULL,
            activity_type VARCHAR(120) NOT NULL,
            detail TEXT NULL,
            created_by INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_itineraries_created_by (created_by),
            INDEX idx_itineraries_trip_time (trip_date, trip_time),
            CONSTRAINT fk_itineraries_created_by
                FOREIGN KEY (created_by) REFERENCES members(id)
                ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """,
        """
        CREATE TABLE IF NOT EXISTS ai_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender VARCHAR(40) NOT NULL,
            message TEXT NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ai_messages_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        """,
    ]

    with db_cursor(commit=True) as cursor:
        for statement in statements:
            cursor.execute(statement)
    seed_members()
def seed_members():

    existing = fetch_one("SELECT COUNT(*) AS total FROM members")

    if existing and existing["total"] > 0:
        return

    default_members = [
        {
            "name": "Long",
            "avatar": "static/images/long.jpg",
            "role_name": "Leader"
        },
        {
            "name": "Hoa",
            "avatar": "static/images/hoa.jpg",
            "role_name": "Photographer"
        },
        {
            "name": "Lan",
            "avatar": "static/images/lan.jpg",
            "role_name": "Planner"
        }
    ]

    for member in default_members:
        execute(
            """
            INSERT INTO members(name, avatar, role_name)
            VALUES(%s,%s,%s)
            """,
            (
                member["name"],
                member["avatar"],
                member["role_name"]
            )
        )
def fetch_all(sql, params=None):
    with db_cursor() as cursor:
        cursor.execute(sql, params or ())
        return list(cursor.fetchall())


def fetch_one(sql, params=None):
    with db_cursor() as cursor:
        cursor.execute(sql, params or ())
        return cursor.fetchone()


def execute(sql, params=None):
    with db_cursor(commit=True) as cursor:
        cursor.execute(sql, params or ())
        return cursor.lastrowid
