// Script pour la navigation entre les sections
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
        // Désactiver tous les onglets et sections
        document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));

        // Activer l'onglet cliqué et sa section correspondante
        item.classList.add('active');
        const sectionId = item.getAttribute('data-section');
        document.getElementById(sectionId).classList.add('active');
    });
});


// Script pour la navigation entre les sections
document.querySelectorAll('.nav-item').forEach(item => {
    item.addEventListener('click', () => {
        // Désactiver tous les onglets et sections
        document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));

        // Activer l'onglet cliqué et sa section correspondante
        item.classList.add('active');
        const sectionId = item.getAttribute('data-section');
        document.getElementById(sectionId).classList.add('active');
    });
});

// Gestion de la console générale
document.addEventListener('DOMContentLoaded', function () {
    const terminalOutput = document.querySelector('#general .terminal-output');
    const terminalInput = document.querySelector('#general .terminal-input input');

    // Initialiser le terminal
    terminalOutput.innerHTML = '<div>Système de Vote</div>' +
        '<div>Terminal Windows - Répertoire: ../</div>' +
        '<div>Type \'help\' pour lister les commandes</div>';

    // Ajouter une ligne à la sortie du terminal
    function addLineToTerminal(text) {
        if (Array.isArray(text)) {
            text.forEach(line => {
                const div = document.createElement('div');
                div.textContent = line;
                terminalOutput.appendChild(div);
            });
        } else {
            const div = document.createElement('div');
            div.textContent = text;
            terminalOutput.appendChild(div);
        }

        // Scroll vers le bas
        terminalOutput.scrollTop = terminalOutput.scrollHeight;
    }

    // Envoyer la commande au serveur
    async function sendCommand(command) {
        addLineToTerminal(`admin@ecovision:~$ ${command}`);

        if (command.toLowerCase() === 'cls' || command.toLowerCase() === 'clear') {
            terminalOutput.innerHTML = '';
            return;
        }

        try {
            const response = await fetch('https://systeme-vote-backend-production.up.railway.app/api/admin/cli.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ command })
            });

            const responseText = await response.text();
            let data;

            try {
                data = JSON.parse(responseText);
            } catch (parseError) {
                // Si le parsing échoue, afficher la réponse brute
                addLineToTerminal("Erreur de parsing JSON. Réponse brute:");
                addLineToTerminal(responseText);
                return;
            }

            if (data.status === 'error') {
                addLineToTerminal(`Erreur: ${data.message}`);
            } else if (data.status === 'success') {
                addLineToTerminal(data.message);
            }
        } catch (error) {
            addLineToTerminal(`Erreur de connexion: ${error.message}`);
        }
    }

    // Gestionnaire d'événement pour l'entrée du terminal
    terminalInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            const command = this.value.trim();
            if (command) {
                sendCommand(command);
                this.value = '';
            }
        }
    });
});


// Gestion de la console MySQL
document.addEventListener('DOMContentLoaded', function () {
    // S'assurer que l'élément existe avant de tenter d'y accéder
    if (!document.querySelector('#sql')) return;

    const mysqlOutput = document.querySelector('#sql .terminal-output');
    const mysqlInput = document.querySelector('#sql .terminal-input input');

    // Initialiser le terminal MySQL
    mysqlOutput.innerHTML = '<div>MySQL [ecovision]> Connecté</div>' +
        '<div>Type \'SHOW TABLES;\' pour voir les tables disponibles</div>' +
        '<div>Type \'HELP;\' pour des exemples de commandes</div>';

    // Ajouter une ligne à la sortie du terminal
    function addLineToMySQLTerminal(text) {
        if (Array.isArray(text)) {
            text.forEach(line => {
                const div = document.createElement('div');
                div.textContent = line;
                mysqlOutput.appendChild(div);
            });
        } else {
            const div = document.createElement('div');
            div.textContent = text;
            mysqlOutput.appendChild(div);
        }

        // Scroll vers le bas
        mysqlOutput.scrollTop = mysqlOutput.scrollHeight;
    }

    // Envoyer la requête SQL au serveur
    async function sendSQLQuery(query) {
        addLineToMySQLTerminal(`MySQL> ${query}`);

        // Commandes spéciales
        if (query.toUpperCase() === 'CLEAR;' || query.toUpperCase() === 'CLS;') {
            mysqlOutput.innerHTML = '';
            return;
        }

        if (query.toUpperCase() === 'HELP;') {
            addLineToMySQLTerminal([
                "Exemples de commandes:",
                "SHOW TABLES; - Affiche la liste des tables",
                "DESCRIBE nom_table; - Affiche la structure d'une table",
                "SELECT * FROM nom_table LIMIT 10; - Affiche les 10 premières lignes d'une table",
                "SELECT COUNT(*) FROM nom_table; - Compte le nombre de lignes dans une table",
                "SHOW VARIABLES LIKE 'version%'; - Affiche la version de MySQL",
                "CLEAR; ou CLS; - Efface l'écran"
            ]);
            return;
        }

        try {
            const response = await fetch('https://systeme-vote-backend-production.up.railway.app/api/admin/sqlcli.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    query,
                    mode: readOnlyMode ? 'readonly' : 'rw'
                })
            });

            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            const data = await response.json();

            if (data.status === 'error') {
                addLineToMySQLTerminal(`Erreur: ${data.message}`);
            } else if (data.status === 'success') {
                addLineToMySQLTerminal(data.message);
            }
        } catch (error) {
            console.error('Error details:', error);
            addLineToMySQLTerminal(`Erreur de connexion: ${error.message}`);
            addLineToMySQLTerminal("Veuillez vérifier que le fichier mysql_cli.php est correctement installé.");
        }
    }

    // Gestionnaire d'événement pour l'entrée du terminal
    mysqlInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter') {
            const query = this.value.trim();
            if (query) {
                sendSQLQuery(query);
                this.value = '';
            }
        }
    });

    // Gérer le bouton de changement de mode (lecture seule / écriture)
    const toggleModeButton = document.querySelector('#sql .toggle-mode');
    const modeDisplay = document.querySelector('#sql .section-header span');
    let readOnlyMode = true;

    if (toggleModeButton) {
        toggleModeButton.addEventListener('click', function () {
            readOnlyMode = !readOnlyMode;
            if (readOnlyMode) {
                modeDisplay.textContent = 'Mode: Lecture seule';
                this.textContent = 'Changer de mode';
                this.classList.remove('btn-danger');
                this.classList.add('btn-primary');
                addLineToMySQLTerminal("Mode lecture seule activé - Les modifications de la base de données sont désactivées");
            } else {
                modeDisplay.textContent = 'Mode: Lecture et écriture';
                this.textContent = 'Retour en lecture seule';
                this.classList.remove('btn-primary');
                this.classList.add('btn-danger');
                addLineToMySQLTerminal("Mode lecture et écriture activé - ATTENTION: Les modifications de la base de données sont autorisées");
            }
        });
    }
});
// Variables globales
let adminStartTime;  // Initialisé depuis
let targetDate;      // Initialisé depuis 
let timerInterval;   // Pour stocker l'intervalle du timer

// Éléments DOM
const timerDisplay = document.querySelector('.timer-display');
const resetButton = document.querySelector('.timer-controls .btn-primary');
const targetDateInput = document.getElementById('target-date');
const setTargetButton = document.querySelector('.btn-success');

// Initialisation du timer
async function initTimer() {
    try {
        // Récupération des données du timer depuis le
        const response = await fetch('https://systeme-vote-backend-production.up.railway.app/api/admin/timer.php', {credentials: 'same-origin'});
        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des données du timer');
        }
        
        const data = await response.json();
        if (data.status === 'success') {
            adminStartTime = new Date(data.data.adminStartTime);
            targetDate = new Date(data.data.targetDate);
            
            console.log("Admin panel: données récupérées du backnd:", {
                adminStartTime: adminStartTime.toISOString(),
                targetDate: targetDate.toISOString()
            });
            
            // Mise à jour de l'input avec la date cible
            targetDateInput.value = targetDate.toISOString().slice(0, 16);
            
            // Démarrage du timer
            updateTimer();
            if (timerInterval) clearInterval(timerInterval);
            timerInterval = setInterval(updateTimer, 1000);
        } else {
            console.error('Admin panel: erreur de récupération:', data.message);
            timerDisplay.textContent = 'Erreur de chargement';
        }
    } catch (error) {
        console.error('Admin panel: erreur d\'initialisation:', error);
        timerDisplay.textContent = 'Erreur de chargement';
    }
}

// Mise à jour de l'affichage du timer
function updateTimer() {
    const now = new Date();
    
    // Calcul du temps restant jusqu'à la date cible
    const timeDiff = targetDate - now;

    if (timeDiff <= 0) {
        timerDisplay.textContent = '00:00:00';
        clearInterval(timerInterval);
        return;
    }

    // Conversion en heures, minutes, secondes
    const hours = Math.floor(timeDiff / (1000 * 60 * 60));
    const minutes = Math.floor((timeDiff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeDiff % (1000 * 60)) / 1000);

    // Formatage de l'affichage
    timerDisplay.textContent = 
        (hours < 10 ? '0' + hours : hours) + ':' +
        (minutes < 10 ? '0' + minutes : minutes) + ':' +
        (seconds < 10 ? '0' + seconds : seconds);
}

// Fonction pour réinitialiser le timer
async function resetTimer() {
    try {
        const response = await fetch('https://systeme-vote-backend-production.up.railway.app/api/admin/timer.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ reset: true })
        });
        
        if (!response.ok) {
            throw new Error('Erreur lors de la réinitialisation du timer');
        }
        
        const data = await response.json();
        if (data.status === 'success') {
            adminStartTime = new Date(data.data.adminStartTime);
            
            console.log("Admin panel: timer réinitialisé, nouvel adminStartTime:", adminStartTime.toISOString());
            initTimer();
            
            updateTimer();
        } else {
            console.error('Admin panel: erreur de réinitialisation:', data.message);
            alert('Erreur lors de la réinitialisation du timer');
        }
    } catch (error) {
        console.error('Admin panel: erreur de réinitialisation:', error);
        alert('Erreur lors de la réinitialisation du timer');
    }
}

// Fonction pour définir une nouvelle date cible
async function setTargetDate() {
    const newTarget = new Date(targetDateInput.value);
    
    if (isNaN(newTarget.getTime())) {
        alert('Date invalide');
        return;
    }
    
    try {
        const response = await fetch('https://systeme-vote-backend-production.up.railway.app/api/admin/timer.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ targetDate: newTarget.toISOString() })
        });
        
        if (!response.ok) {
            throw new Error('Erreur lors de la mise à jour de la date cible');
        }
        
        const data = await response.json();
        if (data.status === 'success') {
            targetDate = new Date(data.data.targetDate);
            
            console.log("Admin panel: nouvelle date cible définie:", targetDate.toISOString());
            
            updateTimer();
        } else {
            console.error('Admin panel: erreur de mise à jour:', data.message);
            alert('Erreur lors de la mise à jour de la date cible');
        }
    } catch (error) {
        console.error('Admin panel: erreur de mise à jour de la date cible:', error);
        alert('Erreur lors de la mise à jour de la date cible');
    }
}

// Fonction pour mettre à jour adminStartTime
async function setAdminStartTime(newTime) {
    try {
        const response = await fetch('https://systeme-vote-backend-production.up.railway.app/api/admin/timer.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ adminStartTime: newTime.toISOString() })
        });
        
        if (!response.ok) {
            throw new Error('Erreur lors de la mise à jour de adminStartTime');
        }
        
        const data = await response.json();
        if (data.status === 'success') {
            adminStartTime = new Date(data.data.adminStartTime);
            
            console.log("Admin panel: adminStartTime mis à jour:", adminStartTime.toISOString());
            
            return true;
        } else {
            console.error('Admin panel: erreur de mise à jour:', data.message);
            return false;
        }
    } catch (error) {
        console.error('Admin panel: erreur de mise à jour de adminStartTime:', error);
        return false;
    }
}

function updateStats() {
    // Appel AJAX pour récupérer les données
    fetch('https://systeme-vote-backend-production.up.railway.app/api/admin/countvote.php', {credentials: 'same-origin'})
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                // Mise à jour des valeurs
                document.getElementById('total-votes').textContent = data.data.totalVotes;
                document.getElementById('active-voters').textContent = data.data.activeVoters;
                updateTimer();
                
                // Si le temps restant est fourni
                if (data.data.remainingMinutes !== undefined) {
                    document.getElementById('remaining-time').textContent = data.data.remainingMinutes;
                }
            } else {
                console.error('Erreur lors de la récupération des statistiques:', data.message);
            }
        })
        .catch(error => {
            console.error('Erreur lors de la récupération des statistiques:', error);
        });
}

// Mettre à jour les statistiques immédiatement au chargement
updateStats();

// Mettre à jour les statistiques toutes les 5 secondes
setInterval(updateStats, 10000);

// Gestionnaires d'événements
document.addEventListener('DOMContentLoaded', () => {
    // Initialisation
    initTimer();
    
    // Bouton de réinitialisation
    resetButton.addEventListener('click', resetTimer);
    
    // Bouton pour définir la date cible
    setTargetButton.addEventListener('click', setTargetDate);
});

document.addEventListener('DOMContentLoaded', () => {
    const lockButton = document.getElementById('lock-button');
    const statusDisplay = document.getElementById('lock-status');

    lockButton.addEventListener('click', async () => {
        // Lire les états des cases à cocher
        const lockVoting = document.getElementById('lock-voting').checked;
        const lockRegistration = document.getElementById('lock-registration').checked;
        const lockResults = document.getElementById('lock-results').checked;

        const payload = {
            lockVoting,
            lockRegistration,
            lockResults
        };

        // Désactiver le bouton pendant l'envoi
        lockButton.disabled = true;
        lockButton.textContent = 'Verrouillage en cours...';

        try {
            const response = await fetch('https://systeme-vote-backend-production.up.railway.app/api/admin/shutdown.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const data = await response.json();

            if (data.status === 'success') {
                statusDisplay.textContent = '🔒 Système Verrouillé';
                statusDisplay.classList.remove('unlocked');
                statusDisplay.classList.add('locked');
            } else {
                statusDisplay.textContent = `Erreur: ${data.message}`;
            }
        } catch (error) {
            console.error('Erreur :', error);
        } finally {
            lockButton.disabled = false;
            lockButton.textContent = 'Verrouiller le Système';
        }
    });
});