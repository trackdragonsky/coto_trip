// =========================
// APP URL HELPERS
// =========================

function appBaseUrl(){

    return (window.APP_BASE_URL || "").replace(/\/$/, "");

}

function appUrl(path = "/"){

    if(/^https?:\/\//.test(path) || path.startsWith("//")){
        return path;
    }

    const normalized = `/${String(path).replace(/^\/+/, "")}`;
    const base = appBaseUrl();

    return normalized === "/" ? `${base}/` : `${base}${normalized}`;

}

function appPath(){

    const base = appBaseUrl();
    let path = window.location.pathname || "/";

    if(base && (path === base || path.startsWith(`${base}/`))){
        path = path.slice(base.length) || "/";
    }

    return `/${path.replace(/^\/+|\/+$/g, "")}`;

}

// =========================
// SMALL UI HELPERS
// =========================

function showIsland(message){

    const island =
    document.getElementById("dynamic-island");

    if(!island){
        return;
    }

    island.textContent = message;

    island.style.opacity = "1";
    island.style.transform = "translateX(-50%) scale(1.05)";

    setTimeout(() => {
        island.style.transform = "translateX(-50%) scale(1)";
    }, 300);

}

function hideLoading(delay = 1200){

    window.setTimeout(() => {

        const loading =
        document.getElementById("loading-screen");

        if(!loading){
            return;
        }

        loading.style.opacity = "0";
        loading.style.pointerEvents = "none";

        window.setTimeout(() => {
            loading.remove();
        }, 500);

    }, delay);

}

function appendChatMessage(container, className, text, id){

    if(!container){
        return null;
    }

    const message =
    document.createElement("div");

    message.className = className;

    if(id){
        message.id = id;
    }

    message.textContent = text;
    container.appendChild(message);
    container.scrollTop = container.scrollHeight;

    return message;

}

// =========================
// SOCKET IO
// =========================

let expenseSocket = null;
let expenseReloadScheduled = false;

function scheduleExpenseReload(){

    if(expenseReloadScheduled || appPath() !== "/expenses"){
        return;
    }

    expenseReloadScheduled = true;

    window.setTimeout(() => {
        window.location.reload();
    }, 250);

}

function initExpenseSocket(){

    if(expenseSocket || typeof io !== "function"){
        return;
    }

    expenseSocket = io({
        transports:["websocket", "polling"],
        reconnectionAttempts:5,
        timeout:8000
    });

    expenseSocket.on("connect", () => {
        if(appPath() === "/expenses"){
            showIsland("Đã kết nối chi phí");
        }
    });

    expenseSocket.on("connect_error", () => {
        if(appPath() === "/expenses"){
            showIsland("Mất kết nối trực tiếp");
        }
    });

    expenseSocket.on("expense_updated", scheduleExpenseReload);

}

initExpenseSocket();

// =========================
// WEATHER EFFECTS
// =========================

function createRain(){

    const rainContainer =
    document.querySelector(".rain-container");

    if(!rainContainer || rainContainer.dataset.ready === "true"){
        return;
    }

    rainContainer.dataset.ready = "true";

    for(let i = 0; i < 80; i++){

        const rain =
        document.createElement("span");

        rain.style.left = Math.random() * 100 + "%";
        rain.style.animationDuration = (Math.random() * 0.5 + 0.5) + "s";
        rain.style.animationDelay = Math.random() * 2 + "s";
        rain.classList.add("rain-drop");

        rainContainer.appendChild(rain);

    }

}

function playCheckinSound(){

    const audio =
    new Audio(
        "https://cdn.pixabay.com/download/audio/2022/03/15/audio_c8c8a73467.mp3?filename=success-1-6297.mp3"
    );

    audio.volume = 0.4;
    audio.play().catch(() => {});

}

// =========================
// PAGE BOOT
// =========================

document.addEventListener("DOMContentLoaded", () => {

    hideLoading();

    const input =
    document.getElementById("chat-input");

    if(input){
        input.addEventListener("keydown", (event) => {
            if(event.key === "Enter"){
                event.preventDefault();
                sendMessage();
            }
        });
    }

    document.body.addEventListener(
        "click",
        () => {
            const music =
            document.getElementById("bg-music");

            if(music){
                music.play().catch(() => {});
            }
        },
        { once:true }
    );

    if(document.body.classList.contains("rainy-theme")){
        createRain();
    }

});

window.addEventListener("scroll", () => {

    const hero =
    document.querySelector(".hero");

    if(hero){
        hero.style.backgroundPositionY = window.scrollY * 0.5 + "px";
    }

});

// =========================
// PREMIUM MOBILE COMPONENTS
// =========================

const tripMembers = window.TRIP_MEMBERS || ["Long", "Hoa", "Linh", "LAnh", "Lan", "Bắc"];

const categoryConfig = [
    { name:"Di chuyển", icon:"bus-front", color:"#2563eb" },
    { name:"Ăn uống", icon:"utensils", color:"#f97316" },
    { name:"Lưu trú", icon:"bed-double", color:"#7c3aed" },
    { name:"Vé", icon:"ticket", color:"#0891b2" },
    { name:"Vui chơi", icon:"party-popper", color:"#ec4899" },
    { name:"Khác", icon:"sparkles", color:"#16a34a" }
];

const activityConfig = {
    "Di chuyển": { icon:"bus-front", color:"#2563eb" },
    "Khách sạn": { icon:"hotel", color:"#7c3aed" },
    "Ăn uống": { icon:"utensils", color:"#f97316" },
    "Chụp ảnh": { icon:"map-pin", color:"#0891b2" },
    "Vui chơi": { icon:"party-popper", color:"#ec4899" },
    "Nghỉ ngơi": { icon:"coffee", color:"#16a34a" }
};

function refreshIcons(){
    if(window.lucide){
        window.lucide.createIcons();
    }
}

function formatVnd(value){
    const number = Number(value) || 0;
    return new Intl.NumberFormat("vi-VN").format(number) + "đ";
}

function parseMoney(value){
    return Number(String(value || "").replace(/[^\d]/g, "")) || 0;
}

function formatDateVi(value){
    return new Intl.DateTimeFormat("vi-VN", {
        day:"2-digit",
        month:"2-digit",
        year:"numeric"
    }).format(new Date(value + "T00:00:00"));
}

function calculateDebts(expenses){
    const total = expenses.reduce((sum, item) => sum + Number(item.amount || 0), 0);
    const perPerson = tripMembers.length ? Math.round(total / tripMembers.length) : 0;
    const paidByMember = Object.fromEntries(tripMembers.map(member => [member.name, 0]));

    expenses.forEach((item) => {
        if(Object.prototype.hasOwnProperty.call(paidByMember, item.payer)){
            paidByMember[item.payer] += Number(item.amount || 0);
        }
    });

    const creditors = [];
    const debtors = [];

    tripMembers.forEach((member) => {
        const balance = Math.round(paidByMember[member.name] - perPerson);
        if(balance > 0){
            creditors.push({member: member.name,amount: balance});
        }
        if(balance < 0){
            debtors.push({member: member.name,amount: Math.abs(balance)});
        }
    });

    const settlements = [];
    let debtorIndex = 0;
    let creditorIndex = 0;

    while(debtorIndex < debtors.length && creditorIndex < creditors.length){
        const debtor = debtors[debtorIndex];
        const creditor = creditors[creditorIndex];
        const amount = Math.min(debtor.amount, creditor.amount);

        if(amount > 0){
            settlements.push({
                from:debtor.member,
                to:creditor.member,
                amount
            });
        }

        debtor.amount -= amount;
        creditor.amount -= amount;

        if(debtor.amount === 0){
            debtorIndex += 1;
        }
        if(creditor.amount === 0){
            creditorIndex += 1;
        }
    }

    const topPayer = Object.entries(paidByMember)
        .sort((a, b) => b[1] - a[1])[0] || ["-", 0];

    return {
        total,
        perPerson,
        paidByMember,
        settlements,
        topPayer
    };
}

function ExpenseCategorySelector(){
    const container = document.getElementById("expense-category-selector");
    const titleInput = document.getElementById("title");
    const categoryInput = document.getElementById("category");

    if(!container || !titleInput || !categoryInput){
        return;
    }

    const selectCategory = (categoryName) => {
        titleInput.value = categoryName;
        categoryInput.value = categoryName;

        container.querySelectorAll(".category-card").forEach((button) => {
            const active = button.dataset.category === categoryName;
            button.classList.toggle("active", active);
            button.style.background = active
                ? `linear-gradient(135deg, ${button.dataset.color}, ${button.dataset.color}cc)`
                : "";
        });
    };

    container.innerHTML = categoryConfig.map((category) => `
        <button class="category-card" type="button" data-category="${category.name}" data-color="${category.color}">
            <i data-lucide="${category.icon}"></i>
            <span>${category.name}</span>
        </button>
    `).join("");

    container.addEventListener("click", (event) => {
        const button = event.target.closest(".category-card");
        if(!button){
            return;
        }
        selectCategory(button.dataset.category);
    });

    selectCategory(categoryInput.value || categoryConfig[0].name);
}

function ExpenseSummary(expenses){
    const container = document.getElementById("expense-summary");
    if(!container){
        return;
    }

    const result = calculateDebts(expenses);
    const paidMemberCount = Object.values(result.paidByMember).filter(amount => amount > 0).length;
    const progress = tripMembers.length
        ? Math.round((paidMemberCount / tripMembers.length) * 100)
        : 0;
    const debtRows = result.settlements.length
        ? result.settlements.map((item) => `
            
        `).join("")
        : ``;

    container.innerHTML = `
        <div class="section-heading">
            <div>
                <p class="eyebrow">Tổng quan chi tiêu</p>
                <h2>Nhóm đang chi ${formatVnd(result.total)}</h2>
            </div>
        </div>
        <div class="expense-progress">
            <div>
                <span>Thành viên đã phát sinh chi phí</span>
                <strong>${paidMemberCount}/${tripMembers.length}</strong>
            </div>
            <div class="expense-progress-track">
                <span style="width:${progress}%"></span>
            </div>
        </div>
        <div class="summary-grid">
            <div class="summary-tile">
                <p>Tổng chi phí</p>
                <strong>${formatVnd(result.total)}</strong>
            </div>
            <div class="summary-tile">
                <p>Mỗi người</p>
                <strong>${formatVnd(result.perPerson)}</strong>
            </div>
        </div>
        <div class="debt-list">${debtRows}</div>
    `;
}

function ExpenseCardList(expenses){
    const container = document.getElementById("expense-list");
    if(!container){
        return;
    }

    if(!expenses.length){
        container.innerHTML = `
            <div class="debt-row">
                <div>
                    <strong>Chưa có chi phí</strong>
                    <p>Thêm khoản đầu tiên để bắt đầu theo dõi</p>
                </div>
                <span>0đ</span>
            </div>
        `;
        return;
    }

    container.innerHTML = expenses.map((expense) => {
        const category = categoryConfig.find(item => item.name === (expense.category || expense.title)) || categoryConfig[0];
        return `
            <article class="expense-row">
                <div class="expense-icon" style="background:${category.color}">
                    <i data-lucide="${category.icon}"></i>
                </div>
                <div>
                    <div class="expense-title">${expense.title}</div>
                    <div class="expense-meta">${expense.payer} đã chi</div>
                    ${expense.note ? `<div class="expense-note">${expense.note}</div>` : ""}
                </div>
                <div class="expense-actions">
                    <div class="expense-amount">
                        ${formatVnd(expense.amount)}
                    </div>

                    <button
                        class="delete-expense-btn"
                        onclick="deleteExpense(${expense.id})"
                    >
                        <i data-lucide="trash-2"></i>
                    </button>
                </div>
            </article>
        `;
    }).join("");
}

function initExpensesPage(){
    if(!document.body.classList.contains("expense-app")){
        return;
    }

    const amountInput = document.getElementById("amount");
    const form = document.getElementById("expense-form");

    ExpenseCategorySelector();
    ExpenseSummary(window.EXPENSE_DATA || []);
    ExpenseCardList(window.EXPENSE_DATA || []);

    if(amountInput){
        amountInput.addEventListener("input", () => {
            const amount = parseMoney(amountInput.value);
            amountInput.value = amount ? new Intl.NumberFormat("vi-VN").format(amount) : "";
        });
    }

    if(form){
        form.addEventListener("submit", (event) => {
            event.preventDefault();
            addExpense();
        });
    }

    refreshIcons();
}

async function addExpense(){
    const titleInput = document.getElementById("title");
    const categoryInput = document.getElementById("category");
    const noteInput = document.getElementById("note");
    const payerInput = document.getElementById("payer");
    const amountInput = document.getElementById("amount");

    if(!titleInput || !payerInput || !amountInput){
        return;
    }

    const amount = parseMoney(amountInput.value);

    if(!titleInput.value || !payerInput.value || amount <= 0){
        showIsland("Nhập đủ danh mục, người chi và số tiền");
        return;
    }

    const button = document.getElementById("add-expense-button");
    if(button){
        button.disabled = true;
        button.dataset.originalText = button.textContent;
        button.textContent = "Đang lưu...";
    }

    const formData = new FormData();
    formData.append("title", titleInput.value);
    formData.append("category", categoryInput ? categoryInput.value : titleInput.value);
    formData.append("note", noteInput ? noteInput.value.trim() : "");
    formData.append("payer", payerInput.value);
    formData.append("amount", String(amount));

    try{
        const response = await fetch(appUrl("/add_expense"), {
            method:"POST",
            body:formData
        });
        const data = await response.json().catch(() => ({}));

        if(!response.ok){
            throw new Error(data.message || "Không thể thêm chi phí");
        }

        showIsland("Đã lưu chi phí");
        amountInput.value = "";
        if(noteInput){
            noteInput.value = "";
        }

        if(!expenseSocket || !expenseSocket.connected){
            scheduleExpenseReload();
        }
    }catch(error){
        console.error(error);
        showIsland(error.message || "Không thể lưu chi phí");
    }finally{
        if(button){
            button.disabled = false;
            button.innerHTML = `<i data-lucide="plus"></i> Thêm chi phí`;
            refreshIcons();
        }
    }
}
async function deleteExpense(id){

    if(!confirm("Xóa khoản chi này?")){
        return;
    }

    try{

        const response = await fetch(appUrl(`/delete_expense/${id}`),{
            method:"DELETE"
        });

        const data = await response.json();

        if(!response.ok){
            throw new Error(data.message || "Không thể xoá");
        }

        // Xóa ngay khỏi dữ liệu hiện tại
        window.EXPENSE_DATA = (window.EXPENSE_DATA || []).filter(
            expense => Number(expense.id) !== Number(id)
        );

        // Render lại giao diện
        ExpenseSummary(window.EXPENSE_DATA);
        ExpenseCardList(window.EXPENSE_DATA);

        refreshIcons();

        showIsland("Đã xoá khoản chi");

    }catch(error){

        console.error(error);
        showIsland(error.message || "Không thể xoá khoản chi");

    }
}
document.addEventListener("DOMContentLoaded",()=>{

    const modal =
        document.getElementById("collection-modal");

    const openBtn =
        document.getElementById("open-collection-modal");

    const closeBtn =
        document.getElementById("close-collection-modal");

    const amountInput =
        document.getElementById("collection-amount");

    if(openBtn){

        openBtn.onclick = ()=>{

            modal.classList.add("show");

            const nav =
                document.querySelector(".bottom-nav");

            if(nav){
                nav.style.display="none";
            }
        };
    }

    if(closeBtn){

        closeBtn.onclick = ()=>{

            modal.classList.remove("show");

            const nav =
                document.querySelector(".bottom-nav");

            if(nav){
                nav.style.display="flex";
            }
        };
    }

    if(amountInput){

        amountInput.addEventListener(
            "input",
            ()=>{
                const value =
                    parseMoney(amountInput.value);

                amountInput.value =
                    value
                    ? new Intl.NumberFormat("vi-VN")
                    .format(value)
                    : "";
            }
        );
    }

});
document
.getElementById("save-collection-btn")
?.addEventListener(
"click",
async ()=>{

    const member =
        document.getElementById("collection-member");

    const amount =
        document.getElementById("collection-amount");

    const formData =
        new FormData();

    formData.append(
        "member_id",
        member.value
    );

    formData.append(
        "amount",
        parseMoney(amount.value)
    );

    try{

        const response =
            await fetch(
                appUrl("/add_collection"),
                {
                    method:"POST",
                    body:formData
                }
            );

        const data =
            await response.json();

        if(!response.ok){

            throw new Error(
                data.message
            );
        }

        location.reload();

    }catch(error){

        alert(error.message);

    }

});
async function deleteCollection(id){

    if(
        !confirm(
            "Xóa khoản thu này?"
        )
    ){
        return;
    }

    try{

        const response =
            await fetch(
                appUrl(`/delete_collection/${id}`),
                {
                    method:"DELETE"
                }
            );

        if(!response.ok){
            throw new Error();
        }

        location.reload();

    }catch{

        alert(
            "Không thể xóa"
        );

    }

}

function GalleryUploader() {

    const fileInput =
        document.getElementById("gallery-file-input");

    const cameraInput =
        document.getElementById("gallery-camera-input");

    const grid =
        document.getElementById("gallery-grid");

    const count =
        document.getElementById("gallery-count");

    const modal =
        document.getElementById("image-preview-modal");

    if (!grid) return;

    const images =
        window.GALLERY_IMAGES || [];

    let currentSlide = 0;

    // =========================
    // UPLOAD
    // =========================

    async function uploadGalleryFiles(files) {

        if (!files || !files.length) {
            showIsland("Chưa chọn ảnh");
            return;
        }

        const formData = new FormData();

        Array.from(files).forEach((file) => {

            if (file.type.startsWith("image/")) {
                formData.append("images", file);
            }

        });

        showIsland("Đang tải ảnh lên...");

        try {

            const response = await fetch(
                appUrl("/upload_gallery"),
                {
                    method: "POST",
                    body: formData
                }
            );

            const data =
                await response.json();

            if (!response.ok) {

                throw new Error(
                    data.message ||
                    "Upload thất bại"
                );

            }

            showIsland("Tải ảnh thành công");

            if (data.items) {

                data.items.reverse()
                    .forEach((item) => {

                        images.unshift(item);

                    });

            }

            currentSlide = 0;

            render();

        } catch (error) {

            console.error(error);

            showIsland(
                error.message ||
                "Không thể tải ảnh"
            );

        }

    }

    // =========================
    // INPUT EVENTS
    // =========================

    fileInput?.addEventListener(
        "change",
        (event) => {

            uploadGalleryFiles(
                event.target.files
            );

            event.target.value = "";

        }
    );

    cameraInput?.addEventListener(
        "change",
        (event) => {

            uploadGalleryFiles(
                event.target.files
            );

            event.target.value = "";

        }
    );

    // =========================
    // RENDER SLIDER
    // =========================

    function render() {

    if (!images.length) {

        grid.innerHTML = `
            <div class="gallery-empty">
                <p>Chưa có ảnh nào</p>
            </div>
        `;

        return;
    }

    const prevIndex =
        currentSlide === 0
            ? images.length - 1
            : currentSlide - 1;

    const nextIndex =
        currentSlide === images.length - 1
            ? 0
            : currentSlide + 1;

    grid.innerHTML = `
        <div
            class="slider-background"
            style="
            background-image:url('${images[currentSlide].src}')
            ">
        </div>
        <div class="carousel-3d">

            <div class="side-image left-image">
                <img src="${images[prevIndex].src}">
            </div>

            <div
                class="main-image"
                data-open="${currentSlide}"
            >
                <img src="${images[currentSlide].src}">
            </div>

            <div class="side-image right-image">
                <img src="${images[nextIndex].src}">
            </div>

        </div>

        <div class="slider-dots">
            ${images.map((_, i) => `
                <span
                    class="dot ${
                        i === currentSlide
                            ? "active"
                            : ""
                    }"
                ></span>
            `).join("")}
        </div>

    `;

    if (count) {
        count.textContent =
            `${images.length} ảnh`;
    }
}

    // =========================
    // NEXT SLIDE
    // =========================

    function nextSlide() {

        grid.style.opacity = "0";

        setTimeout(() => {

            currentSlide =
                (currentSlide + 1)
                % images.length;

            render();

            grid.style.opacity = "1";

        }, 200);

    }

    // =========================
    // PREV SLIDE
    // =========================

    function prevSlide() {

        grid.style.opacity = "0";

        setTimeout(() => {

            currentSlide--;

            if(currentSlide < 0){
                currentSlide =
                    images.length - 1;
            }

            render();

            grid.style.opacity = "1";

        }, 200);

    }

    // =========================
    // AUTO SLIDE
    // =========================

    setInterval(() => {

        if (images.length > 1) {

            grid.style.opacity = "0.6";

            setTimeout(() => {

                nextSlide();

                grid.style.opacity = "1";

            }, 250);

        }

    }, 3000);
    const prevBtn = document.getElementById("prev-slide");
    const nextBtn = document.getElementById("next-slide");

    prevBtn?.addEventListener("click", (e) => {
        e.stopPropagation();
        prevSlide();
    });

    nextBtn?.addEventListener("click", (e) => {
        e.stopPropagation();
        nextSlide();
    });
    // =========================
    // CLICK EVENTS
    // =========================

    grid.addEventListener(
        "click",
        async (event) => {

            const mainImage =
    event.target.closest(".main-image");

    if (!mainImage) {
        return;
    }

const image =
    images[currentSlide];

            if (
                !image ||
                !modal
            ) return;

            const modalImage =
                document.getElementById(
                    "preview-image"
                );

            const downloadBtn =
                document.getElementById(
                    "download-image-btn"
                );

            if (modalImage) {

                modalImage.src =
                    image.src;

                modalImage.alt =
                    image.caption || "";

            }

            if (downloadBtn) {

                downloadBtn.onclick =
                    async (e) => {

                    e.preventDefault();

                    const response =
                        await fetch(
                            image.src
                        );

                    const blob =
                        await response.blob();

                    const url =
                        URL.createObjectURL(
                            blob
                        );

                    const a =
                        document.createElement(
                            "a"
                        );

                    a.href = url;

                    a.download =
                        image.caption ||
                        "image.jpg";

                    document.body
                        .appendChild(a);

                    a.click();

                    a.remove();

                    URL.revokeObjectURL(
                        url
                    );
                };

            }

            modal.classList.add(
                "open"
            );

            modal.setAttribute(
                "aria-hidden",
                "false"
            );

        }
    );

    // =========================
    // CLOSE MODAL
    // =========================

    modal?.addEventListener(
        "click",
        (event) => {

            if (
                event.target === modal ||
                event.target.closest(
                    ".modal-close"
                )
            ) {

                modal.classList.remove(
                    "open"
                );

                modal.setAttribute(
                    "aria-hidden",
                    "true"
                );

            }

        }
    );

    render();

}




function initPremiumPages(){
    initExpensesPage();

    if(document.body.classList.contains("gallery-app")){
        GalleryUploader();
    }

    if(document.body.classList.contains("itinerary-app")){
    renderTripMembers();
    renderItineraries();
    }

    refreshIcons();
}

document.addEventListener("DOMContentLoaded", initPremiumPages);

// =========================
// HOME + AI PREMIUM COMPONENTS
// =========================

function getWeatherIcon(main){
    const icons = {
        Rain:"cloud-rain",
        Thunderstorm:"cloud-lightning",
        Clouds:"cloud",
        Clear:"sun",
        Mist:"cloud-fog"
    };
    return icons[main] || "cloud-sun";
}

function HeroSection(){
    const greeting = document.getElementById("home-greeting");
    const daysNode = document.getElementById("home-days");
    const hoursNode = document.getElementById("home-hours");
    const minutesNode = document.getElementById("home-minutes");

    if(!greeting || !daysNode || !hoursNode || !minutesNode){
        return;
    }

    const hour = new Date().getHours();
    if(hour < 12){
        greeting.textContent = "Chào buổi sáng";
    }else if(hour < 18){
        greeting.textContent = "Chào buổi chiều";
    }else{
        greeting.textContent = "Chào buổi tối";
    }

    const updateCountdown = () => {
        const tripDate = new Date("2026-06-13T00:00:00");
        const diff = Math.max(0, tripDate - new Date());
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));

        daysNode.textContent = String(days).padStart(2, "0");
        hoursNode.textContent = String(hours).padStart(2, "0");
        minutesNode.textContent = String(minutes).padStart(2, "0");
    };

    updateCountdown();
    window.setInterval(updateCountdown, 30000);
}

const WEATHER_TIME_ZONE = "Asia/Ho_Chi_Minh";

function weatherLocalDate(date = new Date()){
    const parts = new Intl.DateTimeFormat("en-CA", {
        timeZone: WEATHER_TIME_ZONE,
        year: "numeric",
        month: "2-digit",
        day: "2-digit"
    }).formatToParts(date).reduce((acc, part) => {
        acc[part.type] = part.value;
        return acc;
    }, {});

    return `${parts.year}-${parts.month}-${parts.day}`;
}

function addWeatherDays(date, days){
    const [year, month, day] = String(date).split("-").map(Number);

    if(!year || !month || !day){
        return date;
    }

    return weatherLocalDate(new Date(Date.UTC(year, month - 1, day + days, 12)));
}

function formatForecastDate(date){
    const today = weatherLocalDate();

    if(date === addWeatherDays(today, 1)){
        return "Ngày mai";
    }

    const parsedDate = new Date(`${date}T00:00:00+07:00`);

    if(Number.isNaN(parsedDate.getTime())){
        return date;
    }

    return new Intl.DateTimeFormat("vi-VN", {
        timeZone: WEATHER_TIME_ZONE,
        weekday:"short",
        day:"2-digit",
        month:"2-digit"
    }).format(parsedDate);
}

function renderWeatherWidget(weather){
    const container = document.getElementById("weather-widget");

    if(!container || !weather){
        return;
    }

    const forecastItems = (weather.forecast || []).slice(0, 4);
    const forecast = forecastItems.map((item) => `
        <article class="mini-forecast-item">
            <span>${formatForecastDate(item.date)}</span>
            <i data-lucide="${getWeatherIcon(item.main)}"></i>
            <strong>${item.temp}°</strong>
        </article>
    `).join("");

    container.innerHTML = `
        <div class="weather-main-row">
            <div>
                <p class="eyebrow">Thời tiết hôm nay</p>
                <h2>${weather.location}</h2>
                <div class="weather-temp-large">${weather.temp}°C</div>
                <p>${weather.description} · Cảm giác ${weather.feelsLike}° · Gió ${weather.wind} km/h</p>
            </div>
            <div class="weather-icon-premium" aria-hidden="true">
                <i data-lucide="${getWeatherIcon(weather.main)}"></i>
            </div>
        </div>
        <div class="mini-forecast-heading">4 ngày tiếp theo</div>
        <div class="mini-forecast">${forecast}</div>
    `;

    refreshIcons();
}

async function fetchLatestWeather(){
    const response = await fetch(appUrl("/api/weather"), { cache: "no-store" });

    if(!response.ok){
        throw new Error("Không thể tải thời tiết mới nhất");
    }

    return response.json();
}

function scheduleWeatherRefresh(){
    let renderedDate = window.HOME_WEATHER?.date || weatherLocalDate();

    const refreshWeather = async (force = false) => {
        const currentDate = weatherLocalDate();

        if(!force && currentDate === renderedDate){
            return;
        }

        try{
            const weather = await fetchLatestWeather();
            window.HOME_WEATHER = weather;
            renderedDate = weather.date || currentDate;
            renderWeatherWidget(weather);
        }catch(error){
            console.warn(error);
        }
    };

    refreshWeather(true);
    window.setInterval(() => refreshWeather(false), 60 * 1000);
    window.setInterval(() => refreshWeather(true), 30 * 60 * 1000);
}

function WeatherWidget(){
    renderWeatherWidget(window.HOME_WEATHER);
    scheduleWeatherRefresh();
}

function MemberPreview(){
    const list = document.getElementById("home-member-list");
    if(!list){
        return;
    }

    const memberMeta = {
        Long:{ avatar:"Lo", role:"Lên kế hoạch" },
        Hoa:{ avatar:"Ho", role:"Ẩm thực" },
        Lan:{ avatar:"La", role:"Ghi hình" }
    };

    list.innerHTML = tripMembers.map((member) => {
        const meta = memberMeta[member] || { avatar:String(member.name || member).slice(0,2), role:"Thành viên" };
        return `
            <article class="home-member-card">
                <div class="home-member-avatar">${meta.avatar}</div>
                <strong>${member}</strong>
                <span>${meta.role}</span>
            </article>
        `;
    }).join("");
}

function QuickActionCard(){
    const grid = document.getElementById("home-quick-actions");
    if(!grid){
        return;
    }

    const actions = [
        {
            href:appUrl("/expenses"),
            title:"Chi phí",
            subtitle:"Chia tiền nhóm",
            icon:"wallet",
            color:"#2563eb"
        },
        {
            href:appUrl("/gallery"),
            title:"Thư viện ảnh",
            subtitle:"Kỷ niệm chuyến đi",
            icon:"images",
            color:"#ec4899"
        },
        {
            href:appUrl("/ai"),
            title:"Trợ lý",
            subtitle:"Gợi ý thông minh",
            icon:"bot",
            color:"#7c3aed"
        },
        {
            href:appUrl("/map"),
            title:"Lịch trình",
            subtitle:"Theo dõi chi tiết",
            icon:"route",
            color:"#f97316"
        }
    ];

    grid.innerHTML = actions.map((action) => `
        <a class="home-action-card" href="${action.href}" style="background:linear-gradient(145deg, ${action.color}18, rgba(255,255,255,0.52))">
            <div class="home-action-icon" style="background:${action.color}">
                <i data-lucide="${action.icon}"></i>
            </div>
            <div>
                <strong>${action.title}</strong>
                <span>${action.subtitle}</span>
            </div>
        </a>
    `).join("");
}

function initHomePage(){
    if(!document.body.classList.contains("home-app")){
        return;
    }

    HeroSection();
    WeatherWidget();
    MemberPreview();
    QuickActionCard();
    refreshIcons();

    document.querySelectorAll(".home-action-card, .memory-card, .stat-card").forEach((card) => {
        card.addEventListener("pointerdown", () => card.style.transform = "scale(0.98)");
        card.addEventListener("pointerup", () => card.style.transform = "");
        card.addEventListener("pointerleave", () => card.style.transform = "");
    });
}

let aiMessages = [];

function MessageBubble(role, text, options = {}){
    const row = document.createElement("div");
    row.className = `message-row ${role}`;

    const avatar = document.createElement("div");
    avatar.className = "message-avatar";
    avatar.textContent = role === "user" ? "Bạn" : "AI";

    const bubble = document.createElement("div");
    bubble.className = "message-bubble";

    if(options.typing){
        bubble.innerHTML = `
            <span class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </span>
        `;
    }else{
        bubble.textContent = text;
    }

    if(role === "user"){
        row.append(bubble, avatar);
    }else{
        row.append(avatar, bubble);
    }

    return row;
}

function renderChatMessages(){
    const chatBox = document.getElementById("chat-box");
    if(!chatBox){
        return;
    }

    chatBox.innerHTML = "";
    aiMessages.forEach((message) => {
        chatBox.appendChild(MessageBubble(message.role, message.text));
    });
    chatBox.scrollTop = chatBox.scrollHeight;
}

function setChatTyping(isTyping){
    const chatBox = document.getElementById("chat-box");
    if(!chatBox){
        return null;
    }

    const existing = document.getElementById("chat-typing-row");
    if(existing){
        existing.remove();
    }

    if(!isTyping){
        return null;
    }

    const row = MessageBubble("ai", "", { typing:true });
    row.id = "chat-typing-row";
    chatBox.appendChild(row);
    chatBox.scrollTop = chatBox.scrollHeight;
    return row;
}

function resizeChatInput(){
    const input = document.getElementById("chat-input");
    if(!input){
        return;
    }

    input.style.height = "auto";
    input.style.height = Math.min(input.scrollHeight, 130) + "px";
}

async function sendMessage(){
    const input = document.getElementById("chat-input");
    const button = document.getElementById("chat-send-button");

    if(!input){
        return;
    }

    const message = input.value.trim();
    if(!message){
        return;
    }

    aiMessages.push({
        role:"user",
        text:message
    });
    renderChatMessages();

    input.value = "";
    resizeChatInput();
    setChatTyping(true);

    if(button){
        button.disabled = true;
    }

    try{
        const response = await fetch(appUrl("/chatbot"), {
            method:"POST",
            headers:{
                "Content-Type":"application/json"
            },
            body:JSON.stringify({
                message
            })
        });

        const data = await response.json().catch(() => ({}));

        if(!response.ok){
            throw new Error(data.reply || "Trợ lý chưa sẵn sàng");
        }

        setChatTyping(false);
        aiMessages.push({
            role:"ai",
            text:data.reply || "Mình chưa có câu trả lời phù hợp."
        });
        renderChatMessages();
        showIsland("Trợ lý đã trả lời");
    }catch(error){
        console.error(error);
        setChatTyping(false);
        aiMessages.push({
            role:"ai",
            text:error.message || "Trợ lý đang bận, thử lại sau nhé."
        });
        renderChatMessages();
        showIsland("Không thể gọi trợ lý");
    }finally{
        if(button){
            button.disabled = false;
        }
    }
}

function quickAsk(text){
    const input = document.getElementById("chat-input");
    if(!input){
        return;
    }

    input.value = text;
    resizeChatInput();
    input.focus();
}

function AIChatContainer(){
    if(!document.body.classList.contains("ai-app")){
        return;
    }

    const input = document.getElementById("chat-input");
    const form = document.getElementById("chat-form");
    const promptPanel = document.querySelector(".prompt-panel");

    aiMessages = [
        {
            role:"ai",
            text:"Xin chào, mình là trợ lý du lịch của chuyến Cô Tô. Bạn muốn tối ưu lịch trình, dự trù chi phí, chọn món ăn hay tìm điểm chụp ảnh trước?"
        }
    ];
    renderChatMessages();

    if(input){
        input.addEventListener("input", resizeChatInput);
        input.addEventListener("keydown", (event) => {
            if(event.key === "Enter" && !event.shiftKey){
                event.preventDefault();
                sendMessage();
            }
        });
        resizeChatInput();
    }

    if(form){
        form.addEventListener("submit", (event) => {
            event.preventDefault();
            sendMessage();
        });
    }

    if(promptPanel){
        promptPanel.addEventListener("click", (event) => {
            const chip = event.target.closest(".prompt-chip");
            if(!chip){
                return;
            }
            quickAsk(chip.dataset.prompt);
        });
    }

    refreshIcons();
}

function initHomeAndAiPages(){
    initHomePage();
    AIChatContainer();
}
//Lịch trình
document.addEventListener("DOMContentLoaded", initHomeAndAiPages);
function renderTripMembers() {
    const container = document.getElementById("member-row");

    if (!container || !window.TRIP_MEMBERS) return;

    container.innerHTML = window.TRIP_MEMBERS.map(member => `
        <div class="member-chip">
            <img src="${member.avatar_url || member.avatar}" alt="${member.name}">
            <span>${member.name}</span>
        </div>
    `).join("");
}
const itineraryIcons = {
    "Di chuyển": "bus-front",
    "Khách sạn": "hotel",
    "Ăn uống": "utensils-crossed",
    "Chụp ảnh": "camera",
    "Vui chơi": "party-popper",
    "Nghỉ ngơi": "bed"
};
const typeColor = {
    "Di chuyển":"move",
    "Khách sạn":"hotel",
    "Ăn uống":"food",
    "Chụp ảnh":"photo",
    "Vui chơi":"fun",
    "Nghỉ ngơi":"rest"
};
function renderItineraries() {

    const container = document.getElementById("itinerary-timeline");

    if (!container) return;

    const items = [...window.ITINERARIES];

    container.innerHTML = items.map(item => {

        const icon = itineraryIcons[item.activity_type] || "calendar";

        return `
        <div class="timeline-card">

            <div class="timeline-left">
                <div class="timeline-icon ${typeColor[item.activity_type] || ''}">
                    <i data-lucide="${icon}"></i>
                </div>
            </div>

            <div class="timeline-content">

                <div class="timeline-header">
                    <h3>${item.title}</h3>

                    <div class="timeline-actions">

                        <button
                            class="icon-btn edit-itinerary"
                            data-id="${item.id}">
                            <i data-lucide="pencil"></i>
                        </button>

                        <button
                            class="icon-btn delete-itinerary"
                            data-id="${item.id}">
                            <i data-lucide="trash-2"></i>
                        </button>

                    </div>
                </div>

                <div class="timeline-meta">
                    ${item.trip_date}
                    •
                    ${item.trip_time}
                </div>

                <div class="timeline-type">
                    ${item.activity_type}
                </div>

                <p>
                    ${item.detail || ""}
                </p>

            </div>

        </div>
        `;
    }).join("");

    lucide.createIcons();

    bindEditButtons();
    bindDeleteButtons();
}
document.addEventListener("DOMContentLoaded", () => {

    const modal =
        document.getElementById("itinerary-modal");

    const form =
        document.getElementById("itinerary-form");

    document
    .getElementById("open-itinerary-modal")
    ?.addEventListener("click", () => {

        form.reset();

        document.getElementById("itinerary-id").value = "";

        document.getElementById("itinerary-modal-title")
            .textContent = "Thêm lịch trình";

        modal.classList.add("active");

        document
        .querySelector(".bottom-nav")
        ?.style.setProperty(
            "display",
            "none",
            "important"
        );
    });

    document
    .getElementById("close-itinerary-modal")
    ?.addEventListener("click", () => {
        const nav = document.querySelector(".bottom-nav");
        modal.classList.remove("active");

        document
        .querySelector(".bottom-nav")
        ?.style.setProperty(
            "display",
            "flex",
            "important"
        );
    });

});

function bindEditButtons() {

    const modal = document.getElementById("itinerary-modal");

    document
        .querySelectorAll(".edit-itinerary")
        .forEach(btn => {

            btn.onclick = () => {

                const id = btn.dataset.id;

                const item = window.ITINERARIES.find(
                    x => x.id == id
                );

                if (!item) return;

                // fill form
                document.getElementById("itinerary-id").value =
                    item.id;

                document.getElementById("itinerary-title").value =
                    item.title || "";

                document.getElementById("itinerary-date").value =
                    item.trip_date || "";

                document.getElementById("itinerary-time").value =
                    item.trip_time || "";

                document.getElementById("itinerary-type").value =
                    item.activity_type || "Di chuyển";

                document.getElementById("itinerary-detail").value =
                    item.detail || "";

                document.getElementById("itinerary-modal-title")
                    .textContent = "Sửa lịch trình";

                modal.setAttribute("aria-hidden", "false");
                modal.classList.add("active");

                document
                    .querySelector(".bottom-nav")
                    ?.style.setProperty(
                        "display",
                        "none",
                        "important"
                    );
            };
        });
}
function closeModal() {

    const modal = document.getElementById("itinerary-modal");

    // bỏ focus để tránh aria warning
    if (document.activeElement) {
        document.activeElement.blur();
    }

    document.getElementById("itinerary-modal").classList.remove("active");
    modal.setAttribute("aria-hidden", "true");

    document.querySelector(".bottom-nav")
        ?.style.setProperty("display", "flex", "important");
}
document
.getElementById("itinerary-form")
?.addEventListener("submit", async function (e) {

    e.preventDefault();

    const id = document.getElementById("itinerary-id").value;

    const payload = {
        title: document.getElementById("itinerary-title").value,
        trip_date: document.getElementById("itinerary-date").value,
        trip_time: document.getElementById("itinerary-time").value,
        activity_type: document.getElementById("itinerary-type").value,
        detail: document.getElementById("itinerary-detail").value
    };

    try {

        let url = appUrl("/add_itinerary");

        if (id) {
            url = appUrl(`/update_itinerary/${id}`);
        }

        const res = await fetch(url, {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify(payload)
        });

        const data = await res.json();

        if (data.status === "success") {

            const updated = data.itinerary;

            // CREATE
            if (!id) {
                window.ITINERARIES.push(updated);
            }
            // UPDATE
            else {
                window.ITINERARIES = window.ITINERARIES.map(x =>
                    x.id == updated.id ? updated : x
                );
            }

            renderItineraries();

            document.getElementById("itinerary-form").reset();
            document.getElementById("itinerary-id").value = "";

            closeModal();
        }

    } catch (err) {
        console.error(err);
    }
});
function bindDeleteButtons(){

    document
    .querySelectorAll(".delete-itinerary")
    .forEach(btn => {

        btn.onclick = async () => {

            const id = btn.dataset.id;

            if(!confirm("Xóa lịch trình này?")){
                return;
            }

            const res = await fetch(
                appUrl(`/delete_itinerary/${id}`),
                {
                    method:"POST"
                }
            );

            const data = await res.json();

            if(data.status === "success"){

                window.ITINERARIES =
                    window.ITINERARIES.filter(
                        x => x.id != id
                    );

                renderItineraries();
            }
        };
    });
}
document.addEventListener("DOMContentLoaded", () => {

    renderTripMembers();

    renderItineraries();

    lucide.createIcons();
});