document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modal");
    const modalTitle = document.getElementById("modalTitle");
    const modalText = document.getElementById("modalText");
    const closeModal = document.getElementById("closeModal");

    // Dictionnaire des descriptions pour chaque vote-card
    const descriptions = {
        "Ville Solaire": {
            text: "Ce projet propose d'installer des panneaux solaires sur les toits des bâtiments publics afin de réduire la dépendance aux énergies fossiles et de favoriser la production d'énergie renouvelable. L'objectif est de maximiser l'autoconsommation énergétique des bâtiments et de réduire la facture énergétique.",
            impact: [
                "Réduction de 40% des émissions de CO₂ des bâtiments municipaux",
                "Économies sur les factures d'électricité",
                "Création d'emplois locaux dans le secteur des énergies renouvelables",
                "Production d'électricité verte"
            ],
            icon: "fas fa-solar-panel",
            choiceId: 4
        },
        "Mobilité Verte": {
            text: "Ce projet vise à promouvoir une mobilité plus durable en développant un réseau dense de pistes cyclables sécurisées et accessibles, tout en augmentant le nombre de vélos en libre-service pour encourager les déplacements non-polluants.",
            impact: [
                "Réduction de 30% du trafic automobile en centre-ville",
                "Diminution de la pollution atmosphérique de 25%",
                "Amélioration de la santé publique grâce à l'activité physique",
                "Réduction du bruit urbain et amélioration de la qualité de vie"
            ],
            icon: "fas fa-bicycle",
            choiceId: 5
        },
        "Forêts Urbaines": {
            text: "Ce projet propose de créer des mini-forêts urbaines à haute densité dans notre ville, offrant des îlots de fraîcheur naturels et favorisant la biodiversité tout en luttant contre la pollution atmosphérique.",
            impact: [
                "Diminution locale de la température de 2 à 5°C en été",
                "Augmentation de la biodiversité de 30% dans les zones concernées",
                "Amélioration de la qualité de l'air avec filtration des particules fines",
                "Création d'espaces de détente accessibles à tous les citoyens"
            ],
            icon: "fas fa-tree",
            choiceId: 6
        },
        "Ville Zéro Gaspillage": {
            text: "Ce projet innovant aborde la gestion de l'eau de manière circulaire, en mettant en place des systèmes de recyclage des eaux grises et de récupération des eaux de pluie pour réduire notre consommation d'eau potable.",
            impact: [
                "Économie de 30% sur la consommation d'eau potable municipale",
                "Réutilisation de 60% des eaux grises pour l'irrigation et le nettoyage",
                "Réduction du risque d'inondation grâce à une meilleure gestion des eaux pluviales",
                "Sensibilisation de la population à l'importance de la préservation de l'eau"
            ],
            icon: "fas fa-tint",
            choiceId: 7
        },
        "Bâtiments Intelligents": {
            text: "L'objectif est de construire des bâtiments autonomes énergétiquement, utilisant des matériaux recyclés et des technologies intelligentes pour optimiser la consommation d'énergie et réduire l'empreinte carbone.",
            impact: [
                "Réduction de 80% des besoins énergétiques par rapport aux bâtiments conventionnels",
                "Utilisation de 65% de matériaux recyclés ou biosourcés",
                "Amélioration du confort des usagers grâce à la domotique",
                "Diminution des coûts de fonctionnement sur le long terme"
            ],
            icon: "fas fa-building",
            choiceId: 8
        },
        "Déchets = Ressources": {
            text: "Ce projet transforme notre approche des déchets en les considérant comme des ressources précieuses. Il inclut un système de compostage obligatoire et un recyclage optimisé pour créer une économie véritablement circulaire.",
            impact: [
                "Réduction de 70% des déchets envoyés en décharge",
                "Production locale de compost pour les espaces verts et jardins partagés",
                "Création d'une filière locale de réemploi",
                "Économie sur les coûts de traitement des déchets"
            ],
            icon: "fas fa-recycle",
            choiceId: 9
        }
    };

    // Fonction pour créer la structure HTML du modal
    function createModalContent(title) {
        const projectData = descriptions[title] || {
            text: "Description non disponible.",
            impact: [],
            icon: "fas fa-leaf",
            choiceId: 0
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
            
            <button class="vote-button" id="vote-button" data-choice-id="${projectData.choiceId}">Voter pour ce projet</button>
        </div>
    `;
    }

    // Fonction pour créer une modal de confirmation personnalisée
    function createConfirmationModal() {
        const confirmModal = document.createElement('div');
        confirmModal.id = 'confirm-modal';
        confirmModal.className = 'modal-overlay';
        confirmModal.innerHTML = `
            <div class="confirmation-box">
                <div class="confirmation-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h3>Confirmation de vote</h3>
                <p>Êtes-vous sûr de vouloir voter pour ce projet ?</p>
                <p class="warning-text">
                    <i class="fas fa-info-circle"></i>
                    Le vote est irréversible et ne peut être effectué qu'une seule fois.
                </p>
                <div class="confirmation-buttons">
                    <button id="confirm-vote" class="confirm-btn">Confirmer mon vote</button>
                    <button id="cancel-vote" class="cancel-btn">Annuler</button>
                </div>
            </div>
        `;
        document.body.appendChild(confirmModal);
        return confirmModal;
    }

    // Fonction pour envoyer le vote
    async function submitVote(choiceId) {
        try {
            const formData = new FormData();
            formData.append('choice_id', choiceId);

            const response = await fetch('/backend/api/vote.php', {
                method: 'POST',
                body: formData,
                credentials: 'include'
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Erreur lors de la requête:', error);
            throw error;
        }
    }

    // Fonction pour afficher un message d'erreur élégant
    function showErrorMessage(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-toast';
        errorDiv.innerHTML = `
            <i class="fas fa-exclamation-circle"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(errorDiv);
        
        setTimeout(() => {
            errorDiv.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            errorDiv.classList.remove('show');
            setTimeout(() => {
                errorDiv.remove();
            }, 300);
        }, 3000);
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
            document.getElementById("vote-button").addEventListener("click", async () => {
                const voteButton = document.getElementById("vote-button");
                const choiceId = voteButton.dataset.choiceId;
                
                // Créer la modal de confirmation
                const confirmModal = createConfirmationModal();
                confirmModal.classList.add('active');
                
                // Gérer la confirmation du vote
                document.getElementById("confirm-vote").addEventListener("click", async () => {
                    confirmModal.remove();
                    
                    // Désactiver le bouton pendant l'envoi
                    voteButton.disabled = true;
                    voteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi du vote...';
                    
                    try {
                        const result = await submitVote(choiceId);
                        
                        if (result.status === 'success') {
                            // Animation de confirmation
                            voteButton.innerHTML = '<i class="fas fa-check"></i> Voté !';
                            voteButton.classList.add('voted');
                            
                            // Fermer la modal après un délai
                            setTimeout(() => {
                                modal.classList.remove("active");
                            }, 1500);
                        } else {
                            // Afficher le message d'erreur
                            showErrorMessage(result.message);
                            voteButton.disabled = false;
                            voteButton.innerHTML = 'Voter pour ce projet';
                        }
                    } catch (error) {
                        showErrorMessage('Une erreur est survenue lors du vote. Veuillez réessayer.');
                        voteButton.disabled = false;
                        voteButton.innerHTML = 'Voter pour ce projet';
                    }
                });
                
                // Gérer l'annulation
                document.getElementById("cancel-vote").addEventListener("click", () => {
                    confirmModal.remove();
                });
                
                // Fermer avec la touche Escape
                const handleEscape = (e) => {
                    if (e.key === "Escape" && document.getElementById('confirm-modal')) {
                        confirmModal.remove();
                        document.removeEventListener("keydown", handleEscape);
                    }
                };
                document.addEventListener("keydown", handleEscape);
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



document.addEventListener('DOMContentLoaded', function() {
    checkVoteStatus();
});

function checkVoteStatus() {
    const voteNow = document.querySelector('.vote-now');
    const cantVote = document.querySelector('.cant-vote');
    const alreadyVoted = document.querySelector('.already-voted');
    
    // Cacher tous les messages par défaut
    voteNow.style.display = 'none';
    cantVote.style.display = 'none';
    alreadyVoted.style.display = 'none';
    
    // Vérifier si l'utilisateur est connecté
    fetch('/backend/api/session.php?texte=IsConnected')
        .then(response => response.text())
        .then(data => {
            if (data === 'Yes') {
                // L'utilisateur est connecté, vérifier s'il a déjà voté
                checkIfUserHasVoted();
            } else {
                // L'utilisateur n'est pas connecté
                cantVote.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Erreur lors de la vérification de la session:', error);
            cantVote.style.display = 'block';
        });
}

function checkIfUserHasVoted() {
    // Vous devrez implémenter cette vérification selon votre système de vote
    // Par exemple, en ajoutant une case dans session.php pour "HasVoted"
    fetch('/backend/api/session.php?texte=HasVoted')
        .then(response => response.text())
        .then(data => {
            if (data === 'Yes') {
                document.querySelector('.already-voted').style.display = 'block';
            } else {
                document.querySelector('.vote-now').style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Erreur lors de la vérification du vote:', error);
            document.querySelector('.vote-now').style.display = 'block';
        });
}