// =========================
// MOBILE MENU TOGGLE
// =========================
const MenuBtn = document.getElementById("MenuBtn");
const navMenu = document.querySelector("nav ul");

if (MenuBtn) {
    MenuBtn.addEventListener("click", () => {
        document.body.classList.toggle("mobile-nav-active");
    });
}

// Close menu when clicking outside
document.addEventListener("click", (event) => {
    if (
        MenuBtn &&
        !MenuBtn.contains(event.target) &&
        navMenu &&
        !navMenu.contains(event.target)
    ) {
        document.body.classList.remove("mobile-nav-active");
    }
});


// =========================
// LOGIN MODAL
// =========================
const modal = document.getElementById("login-modal");
const searchField = document.getElementById("search-field");
const closeModalBtn = document.querySelector(".modal-close");

if (searchField && modal) {
    searchField.addEventListener("click", () => {
        modal.style.display = "flex";
    });
}

if (closeModalBtn && modal) {
    closeModalBtn.addEventListener("click", () => {
        modal.style.display = "none";
    });
}

// Close modal by clicking outside
document.addEventListener("click", (event) => {
    if (modal && event.target === modal) {
        modal.style.display = "none";
    }
});
