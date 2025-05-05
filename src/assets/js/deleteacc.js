// Fonction pour afficher le formulaire de confirmation de suppression de compte
function showDeleteAccountConfirmation() {
    // Récupérer l'élément main pour y insérer le formulaire de confirmation
    const mainElement = document.querySelector('main');
    
    // Sauvegarder le contenu actuel du main pour pouvoir revenir en arrière
    const originalContent = mainElement.innerHTML;
    
    // Remplacer le contenu du main par le formulaire de confirmation de suppression
    mainElement.innerHTML = `
      <section class="account-delete-section">
        <h2 class="form-title">Supprimer mon compte</h2>
        <div class="error-message" style="margin:3vh";>
          <p>Attention: cette action est irréversible. Toutes vos données seront définitivement supprimées, et votre vote ne sera plus comptabilisé.</p>
        </div>
        <form id="delete-account-form">
          <div class="password-container">
            <input type="password" id="confirm-password" placeholder="Entrez votre mot de passe pour confirmer" required>
            <img src="assets/img/oeilferme.png" alt="Afficher le mot de passe" class="password-toggle image-bold" id="toggle-confirm-password">
          </div>
          <p id="delete-account-error" class="error-message"></p>
          <div class="form-actions">
            <button type="button" id="cancel-account-deletion" class="profil-btn">Annuler</button>
            <button type="submit" class="profil-btn profil-btn-danger">Confirmer la suppression</button>
          </div>
        </form>
      </section>
    `;
  
    // Validation du formulaire de suppression de compte
    document.getElementById('delete-account-form').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const password = document.getElementById('confirm-password').value;
      const errorMessage = document.getElementById('delete-account-error');
      
      // Vérification si le mot de passe est rempli
      if (!password) {
        errorMessage.textContent = 'Veuillez saisir votre mot de passe pour confirmer la suppression.';
        return;
      }
      
      // Préparer les données pour la requête
      const formData = new FormData();
      const hashpassword = CryptoJS.SHA256(password).toString(CryptoJS.enc.Hex);
      formData.append('password', hashpassword);
      
      // Envoyer la requête au serveur
      fetch('https://systeme-vote-backend-production.up.railway.app/api/delete-account.php', {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === "success" && data.message === "Suppression du compte.") {
          errorMessage.textContent = "Votre compte a été supprimé avec succès.";
          document.body.style.cursor = "wait";

          setTimeout(() => {
            window.location.href = "index";
          }, 3000);
        } else {
          errorMessage.textContent = data.message;
          document.body.style.cursor = "default";
        }
      })
      .catch(error => {
        errorMessage.textContent = 'Une erreur de communication avec le serveur est survenue.';
        console.error('Erreur.');
      });

    });
  
    // Bouton d'annulation
    document.getElementById('cancel-account-deletion').addEventListener('click', restoreOriginalContent);
  
    // Fonction pour restaurer le contenu original
    function restoreOriginalContent() {
      mainElement.innerHTML = originalContent;
      
      // Réattacher l'événement au bouton de changement de mot de passe
      const changePasswordBtn = document.getElementById('password-change-container');
      if (changePasswordBtn) {
        changePasswordBtn.addEventListener('click', showChangePasswordForm);
      }
      
      // Réattacher l'événement au bouton de suppression de compte
      const deleteAccountBtn = document.getElementById('delete-account');
      if (deleteAccountBtn) {
        deleteAccountBtn.addEventListener('click', showDeleteAccountConfirmation);
      }
    }
  
    // Toggle pour le mot de passe de confirmation
    document.getElementById('toggle-confirm-password').addEventListener('click', function() {
      togglePasswordVisibility('confirm-password', this);
    });
  
    // Fonction pour afficher/masquer un mot de passe
    function togglePasswordVisibility(inputId, toggleIcon) {
      const passwordField = document.getElementById(inputId);
      if (passwordField.type === 'password') {
        passwordField.type = 'text';
        toggleIcon.src = 'assets/img/oeilouvert.png';
      } else {
        passwordField.type = 'password';
        toggleIcon.src = 'assets/img/oeilferme.png';
      }
    }
  }
  
  // Attacher l'événement au bouton de suppression de compte
  document.addEventListener('DOMContentLoaded', function() {
    const deleteAccountBtn = document.getElementById('delete-account');
    if (deleteAccountBtn) {
      deleteAccountBtn.addEventListener('click', showDeleteAccountConfirmation);
    } else {
      console.error("Le bouton de suppression de compte n'a pas été trouvé");
    }
  });