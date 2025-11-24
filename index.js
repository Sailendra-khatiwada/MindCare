//Header toggle
let MenuBtn = document.getElementById('MenuBtn');

MenuBtn.addEventListener('click', function () {
    document.querySelector('body').classList.toggle('mobile-nav-active');
}

);

window.addEventListener('click', function (e) {
    if (!MenuBtn.contains(e.target) && !document.querySelector('nav ul').contains(e.target)) {
        document.body.classList.remove('mobile-nav-active');
    }
});

// Login modal
var modal = document.getElementById("login-modal");

var searchField = document.getElementById("search-field");

var closeModal = document.getElementsByClassName("close")[0];

searchField.addEventListener('click', function () {
    modal.style.display = "flex";
});

closeModal.addEventListener('click', function () {
    modal.style.display = "none";
});

window.onclick = function (event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
};


