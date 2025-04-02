document.addEventListener("DOMContentLoaded", function () {
    function updateTextAndHeader() {
        const header = document.querySelector("header.navbar");
        let burgerMenu = document.querySelector(".burger-menu");
        let mobileNav = document.querySelector(".mobile-nav");

        if (window.matchMedia("(max-width: 768px)").matches) {
            
            if (!burgerMenu) {
                burgerMenu = document.createElement("div");
                burgerMenu.classList.add("burger-menu");
                burgerMenu.innerHTML = "&#9776;"; // Icône burger
                document.body.appendChild(burgerMenu);
            }
            
            if (!mobileNav) {
                mobileNav = document.createElement("nav");
                mobileNav.classList.add("mobile-nav");
                mobileNav.innerHTML = `
                    <ul>
                        <li><a href="index">Accueil</a></li>
                        <li><a href="profil">Profil</a></li>
                        <li><a href="vote">Vote</a></li>
                        <li><a href="resultats">Résultats</a></li>
                        <li><a href="contact">Contact</a></li>
                    </ul>
                `;
                document.body.appendChild(mobileNav);
            }
            
            burgerMenu.addEventListener("click", function () {
                mobileNav.classList.toggle("open");
            });
            
            document.addEventListener("click", function (event) {
                if (!mobileNav.contains(event.target) && !burgerMenu.contains(event.target)) {
                    mobileNav.classList.remove("open");
                }
            });
            
        } else {
            if (burgerMenu) {
                burgerMenu.remove();
            }
            if (mobileNav) {
                mobileNav.remove();
            }
        }
    }
    
    updateTextAndHeader();
    window.matchMedia("(max-width: 768px)").addEventListener("change", updateTextAndHeader);
});
