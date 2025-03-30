document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modal");
    const modalTitle = document.getElementById("modalTitle");
    const modalText = document.getElementById("modalText");
    const closeModal = document.getElementById("closeModal");

    // Dictionnaire des descriptions pour chaque vote-card
    // Données enrichies pour chaque thème
    const descriptions = {
        "Ville Solaire": {
            text: "Ce projet propose d'installer des panneaux solaires sur les toits des bâtiments publics afin de réduire la dépendance aux énergies fossiles et de favoriser la production d'énergie renouvelable. L'objectif est de maximiser l'autoconsommation énergétique des bâtiments et de réduire la facture énergétique.",
            impact: [
                "Réduction de 40% des émissions de CO₂ des bâtiments municipaux",
                "Économies annuelles estimées à 250 000€ sur les factures d'électricité",
                "Création de 35 emplois locaux dans le secteur des énergies renouvelables",
                "Production de 1,2 GWh d'électricité verte par an"
            ],
            icon: "fas fa-solar-panel"
        },
        "Mobilité Verte": {
            text: "Ce projet vise à promouvoir une mobilité plus durable en développant un réseau dense de pistes cyclables sécurisées et accessibles, tout en augmentant le nombre de vélos en libre-service pour encourager les déplacements non-polluants.",
            impact: [
                "Réduction de 30% du trafic automobile en centre-ville",
                "Diminution de la pollution atmosphérique de 25%",
                "Amélioration de la santé publique grâce à l'activité physique",
                "Réduction du bruit urbain et amélioration de la qualité de vie"
            ],
            icon: "fas fa-bicycle"
        },
        "Forêts Urbaines": {
            text: "Ce projet propose de créer des mini-forêts urbaines à haute densité dans notre ville, offrant des îlots de fraîcheur naturels et favorisant la biodiversité tout en luttant contre la pollution atmosphérique.",
            impact: [
                "Diminution locale de la température de 2 à 5°C en été",
                "Augmentation de la biodiversité de 30% dans les zones concernées",
                "Amélioration de la qualité de l'air avec filtration des particules fines",
                "Création d'espaces de détente accessibles à tous les citoyens"
            ],
            icon: "fas fa-tree"
        },
        "Ville Zéro Gaspillage": {
            text: "Ce projet innovant aborde la gestion de l'eau de manière circulaire, en mettant en place des systèmes de recyclage des eaux grises et de récupération des eaux de pluie pour réduire notre consommation d'eau potable.",
            impact: [
                "Économie de 30% sur la consommation d'eau potable municipale",
                "Réutilisation de 60% des eaux grises pour l'irrigation et le nettoyage",
                "Réduction du risque d'inondation grâce à une meilleure gestion des eaux pluviales",
                "Sensibilisation de la population à l'importance de la préservation de l'eau"
            ],
            icon: "fas fa-tint"
        },
        "Bâtiments Intelligents": {
            text: "L'objectif est de construire des bâtiments autonomes énergétiquement, utilisant des matériaux recyclés et des technologies intelligentes pour optimiser la consommation d'énergie et réduire l'empreinte carbone.",
            impact: [
                "Réduction de 80% des besoins énergétiques par rapport aux bâtiments conventionnels",
                "Utilisation de 65% de matériaux recyclés ou biosourcés",
                "Amélioration du confort des usagers grâce à la domotique",
                "Diminution des coûts de fonctionnement sur le long terme"
            ],
            icon: "fas fa-building"
        },
        "Déchets = Ressources": {
            text: "Ce projet transforme notre approche des déchets en les considérant comme des ressources précieuses. Il inclut un système de compostage obligatoire et un recyclage optimisé pour créer une économie véritablement circulaire.",
            impact: [
                "Réduction de 70% des déchets envoyés en décharge",
                "Production locale de compost pour les espaces verts et jardins partagés",
                "Création d'une filière locale de réemploi créant 40 emplois",
                "Économie de 200 000€ par an sur les coûts de traitement des déchets"
            ],
            icon: "fas fa-recycle"
        }
    };

    // Fonction pour créer la structure HTML du modal
    function createModalContent(title) {
        const projectData = descriptions[title] || {
            text: "Description non disponible.",
            impact: [],
            icon: "fas fa-leaf"
        };

        return `
        <div class="modal-content">
            <button class="close-modal" id="close-modal"></button>
            <div class="modal-icon">
                <i class="${projectData.icon}"></i>
            </div>
            <h2 class="modal-title">${title}</h2>
            <div class="modal-text">
                ${projectData.text}
            </div>
            
            <div class="modal-impact">
                <h3 class="impact-title">Impact sur notre communauté :</h3>
                <ul class="impact-list">
                    ${projectData.impact.map(impact => `<li>${impact}</li>`).join('')}
                </ul>
            </div>
            
            <button class="vote-button" id="vote-button">Voter pour ce projet</button>
        </div>
    `;
    }

    // Ouvre la modale lors du clic sur une carte
    document.querySelectorAll(".vote-card").forEach(card => {
        card.addEventListener("click", () => {
            const title = card.querySelector(".vote-title").textContent;

            // Injecter le contenu dans la modal
            modal.innerHTML = createModalContent(title);

            // Ajouter la classe active pour afficher la modal
            modal.classList.add("active");

            // Attacher les événements pour le bouton de fermeture
            document.getElementById("close-modal").addEventListener("click", () => {
                modal.classList.remove("active");
            });

            // Attacher les événements pour le bouton de vote
            document.getElementById("vote-button").addEventListener("click", () => {
                // Animation de confirmation
                const voteButton = document.getElementById("vote-button");
                voteButton.innerHTML = '<i class="fas fa-check"></i>Voté !';
                voteButton.classList.add('voted');

                // Fermer la modal après un délai
                setTimeout(() => {
                    modal.classList.remove("active");
                }, 1500);
            });
        });
    });

    // Fermeture de la modal avec la touche Escape
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && modal.classList.contains("active")) {
            modal.classList.remove("active");
        }
    });

    // Ferme la modale en cliquant sur le bouton ou à l'extérieur
    closeModal.addEventListener("click", () => modal.classList.remove("active"));
    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.classList.remove("active");
        }
    });
});
