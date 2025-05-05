// Variables globales
let adminStartTime; // Initialisé depuis le backend
let targetDate;     // Initialisé depuis le backend (date cible pour le compte à rebours)
let currentTime;    // Variable pour stocker l'heure actuelle

// Fonction pour récupérer les données du timer depuis le backend
async function getTimerData() {
    try {
        const response = await fetch('https://systeme-vote-backend-production.up.railway.app/api/read-timer.php');
        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des données du timer');
        }
        
        const result = await response.json();
        data = result.data;
        adminStartTime = new Date(data.adminStartTime);
        targetDate = new Date(data.targetDate);
        
        
        return data;
    } catch (error) {
        console.error('Mini-timer: erreur lors de la récupération des données du timer');
        // Valeurs par défaut en cas d'échec
        adminStartTime = new Date('2025-04-17T12:00:00');
        targetDate = new Date('2025-04-01T11:30:00');
        return null;
    }
}

// Fonction pour récupérer l'heure du serveur
async function getCurrentTime() {
    try {
        const response = await fetch('https://systeme-vote-backend-production.up.railway.app/api/time.php');
        const data = await response.json();
        currentTime = new Date(data.datetime);

    } catch (error) {
        console.error('Mini-timer: erreur API (backend):');
        currentTime = new Date(); // Utilise l'heure locale si l'API échoue
    }
}

// Fonction pour mettre à jour l'affichage du mini-timer
function updateMiniTimer() {
    const miniTimerElement = document.getElementById('mini-timer');
    if (!miniTimerElement) {
        console.error("Mini-timer: élément 'mini-timer' non trouvé dans le DOM");
        return;
    }
    
    // Met à jour l'heure locale à chaque itération
    currentTime = new Date();
    
    // Calcule le temps restant jusqu'à la date cible (targetDate)
    const timeRemaining = targetDate - currentTime;
    
    if (timeRemaining <= 0) {
        miniTimerElement.textContent = "Temps écoulé!";
    } else {
        let days = Math.floor(timeRemaining / (3600 * 24 * 1000));
        let hours = Math.floor((timeRemaining % (3600 * 24 * 1000)) / (3600 * 1000));
        let minutes = Math.floor((timeRemaining % (3600 * 1000)) / (60 * 1000));
        let displayText = days > 0 ? `${days} jour${days > 1 ? 's' : ''}` : `${hours} h ${minutes} m`;
        
        miniTimerElement.textContent = displayText;
    }
}

// Fonction pour démarrer le mini-timer
async function startMiniTimer() {
    const miniTimerElement = document.getElementById('mini-timer');
    if (!miniTimerElement) {
        return;
    }
    
    miniTimerElement.textContent = "Chargement...";
    
    // Récupère les données du timer et l'heure actuelle
    await getTimerData();
    await getCurrentTime();
    
    // Met à jour immédiatement
    updateMiniTimer();
    
    // Met à jour toutes les minutes
    const updateInterval = setInterval(function() {
        updateMiniTimer();
    }, 60000); // 60000 ms = 1 minute
    
    // Rafraîchit les données du backend périodiquement (toutes les 5 minutes)
    setInterval(async function() {
        await getTimerData();
    }, 300000); // 300000 ms = 5 minutes
}

// Démarrer le mini-timer au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    startMiniTimer();
});