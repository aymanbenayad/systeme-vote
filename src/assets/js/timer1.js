// Variables globales
let adminStartTime; // Initialisé depuis le backend
let targetDate;     // Initialisé depuis le backend (date cible pour le compte à rebours)
let currentTime;    // Variable pour stocker l'heure actuelle

// Fonction pour récupérer les données du timer depuis le backend
async function getTimerData() {
    try {
        const response = await fetch('backend/api/read-timer.php');
        if (!response.ok) {
            throw new Error('Erreur lors de la récupération des données du timer');
        }
        
        const result = await response.json();
        data = result.data;
        adminStartTime = new Date(data.adminStartTime);
        targetDate = new Date(data.targetDate);

        
        return data;
    } catch (error) {
        console.error('Erreur lors de la récupération des données du timer:');
        // Valeurs par défaut en cas d'échec
        adminStartTime = new Date('2025-04-17T12:00:00');
        targetDate = new Date('2025-04-01T11:30:00');

        return null;
    }
}

// Fonction pour récupérer l'heure du serveur
async function getCurrentTime() {
    try {
        const response = await fetch('backend/api/time.php');
        const data = await response.json();
        currentTime = new Date(data.datetime);
    } catch (error) {
        console.error('Erreur API (backend)');
        currentTime = new Date(); // Utilise l'heure locale si l'API échoue
    }
}

// Fonction principale pour démarrer le timer
async function startTimer() {
    const timerElement = document.getElementById('timer');
    
    timerElement.textContent = "Chargement...";
    
    // Récupère les données du timer et l'heure actuelle
    await getTimerData();
    await getCurrentTime();
    
    // Met à jour le timer immédiatement puis toutes les secondes
    updateTimerDisplay();
    
    const interval = setInterval(function() {
        updateTimerDisplay();
    }, 1000);
    
    // Rafraîchit les données du backend périodiquement (toutes les 5 minutes)
    setInterval(async function() {
        await getTimerData();
    }, 300000); // 300000 ms = 5 minutes
    
    // Fonction interne pour mettre à jour l'affichage du timer
    function updateTimerDisplay() {
        // Met à jour l'heure actuelle
        currentTime = new Date();
        
        // Calcule le temps restant jusqu'à la date cible (targetDate)
        const timeRemaining = targetDate - currentTime;
        
        if (timeRemaining <= 0) {
            clearInterval(interval);
            timerElement.textContent = "Temps écoulé!";
        } else {
            let days = Math.floor(timeRemaining / (3600 * 24 * 1000));
            let hours = Math.floor((timeRemaining % (3600 * 24 * 1000)) / (3600 * 1000));
            let minutes = Math.floor((timeRemaining % (3600 * 1000)) / (60 * 1000));
            let seconds = Math.floor((timeRemaining % (60 * 1000)) / 1000);
            
            timerElement.textContent = `${days} d ${hours} h ${minutes} m ${seconds} s`;
        }
    }
}

// Démarrer le timer au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    startTimer();
});