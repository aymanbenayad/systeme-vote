document.addEventListener('DOMContentLoaded', function() {
    // Vérifie si FingerprintJS est disponible
    if (typeof FingerprintJS !== 'undefined') {
      // Génère le fingerprint
      FingerprintJS.load()
        .then(fp => fp.get())
        .then(result => {
          const visitorId = result.visitorId;
          console.log("Fingerprint généré:", visitorId);
          
          // Stocke le fingerprint comme variable globale
          window.userFingerprint = visitorId;
          
          // Modifie la fonction de création des formulaires pour inclure le fingerprint
          const originalShowSignupForm = window.showSignupForm || function(){};
          window.showSignupForm = function() {
            originalShowSignupForm.apply(this, arguments);
            // Ajoute le fingerprint au formulaire d'inscription après sa création
            setTimeout(function() {
              const fingerprintInput = document.getElementById('fingerprint-input');
              if (fingerprintInput) {
                fingerprintInput.value = window.userFingerprint;
                console.log("Fingerprint ajouté au formulaire d'inscription:", window.userFingerprint);
              }
            }, 100);
          };
          
          const originalShowLoginForm = window.showLoginForm || function(){};
          window.showLoginForm = function() {
            originalShowLoginForm.apply(this, arguments);
            // Ajoute le fingerprint au formulaire de connexion après sa création
            setTimeout(function() {
              const fingerprintInput = document.getElementById('login-fingerprint-input');
              if (fingerprintInput) {
                fingerprintInput.value = window.userFingerprint;
                console.log("Fingerprint ajouté au formulaire de connexion:", window.userFingerprint);
              }
            }, 100);
          };
        })
        .catch(error => {
          console.error("Erreur lors de la génération du fingerprint:", error);
        });
    } else {
      console.error("FingerprintJS n'est pas disponible");
    }
  });