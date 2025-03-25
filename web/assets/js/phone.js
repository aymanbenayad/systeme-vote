document.addEventListener("DOMContentLoaded", function () {
    function updateTextAndHeader() {
        const header = document.querySelector("header.navbar");
        let burgerMenu = document.querySelector(".burger-menu");
        let mobileNav = document.querySelector(".mobile-nav");

        if (window.matchMedia("(max-width: 768px)").matches) {

            // Ajouter le menu burger si absent
            if (!burgerMenu) {
                burgerMenu = document.createElement("div");
                burgerMenu.classList.add("burger-menu");
                burgerMenu.innerHTML = "&#9776;"; // Icône burger
                document.body.appendChild(burgerMenu);
            }

            // Ajouter le menu mobile si absent
            if (!mobileNav) {
                mobileNav = document.createElement("nav");
                mobileNav.classList.add("mobile-nav");
                mobileNav.innerHTML = `
                    <ul>
                        <li><a href="/index.html">Accueil</a></li>
                        <li><a href="profil.html">Profil</a></li>
                        <li><a href="vote.html">Vote</a></li>
                        <li><a href="resultats.html">Résultats</a></li>
                        <li><a href="cryptoJS.html">À propos</a></li>
                    </ul>
                    <div class="social-icons-phone">
                        <a href="https://www.linkedin.com/in/aymanbenayad" target="_blank" rel="noopener noreferrer">
                            <img src="/assets/img/linkedinlogo.png" alt="LinkedIn" class="social-icon-phone" style="margin-top: -3px;;">
                        </a>
                        <a href="https://www.instagram.com/ensias.official" target="_blank" rel="noopener noreferrer">
                            <img src="/assets/img/instagramlogo.png" alt="Instagram" class="social-phone">
                        </a>
                        <a href="https://x.com/ensias_official" target="_blank" rel="noopener noreferrer">
                            <img src="/assets/img/twitterlogo.png" alt="Twitter" class="social-phone">
                        </a>
                    </div>
                `;
                document.body.appendChild(mobileNav);
            }

            // Gestion du clic sur le menu burger
            burgerMenu.addEventListener("click", function () {
                mobileNav.classList.toggle("open");
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
