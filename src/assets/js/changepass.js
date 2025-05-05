// Fonction pour afficher le formulaire de changement de mot de passe
function showChangePasswordForm() {
  // Récupérer l'élément main pour y insérer le formulaire
  const mainElement = document.querySelector('main');
  
  // Sauvegarder le contenu actuel du main pour pouvoir revenir en arrière
  const originalContent = mainElement.innerHTML;
  
  // Remplacer le contenu du main par le formulaire de changement de mot de passe
  mainElement.innerHTML = `
    <section class="password-change-section">
      <h2 class="form-title">Changer mon mot de passe</h2>
      <form id="change-password-form">
        <div class="password-container">
          <input type="password" id="old-password" placeholder="Ancien mot de passe" required>
          <img src="assets/img/oeilferme.png" alt="Afficher le mot de passe" class="password-toggle image-bold" id="toggle-old-password">
        </div>
        <div class="password-container">
          <input type="password" id="new-password" placeholder="Nouveau mot de passe" required>
          <img src="assets/img/oeilferme.png" alt="Afficher le mot de passe" class="password-toggle image-bold" id="toggle-new-password">
        </div>
        <div class="password-container">
          <input type="password" id="confirm-new-password" placeholder="Confirmer le nouveau mot de passe" required>
          <img src="assets/img/oeilferme.png" alt="Afficher le mot de passe" class="password-toggle image-bold" id="toggle-confirm-new-password">
        </div>
        <p id="password-change-error" class="error-message"></p>
        <div class="form-actions">
          <button type="button" id="cancel-password-change" class="profil-btn">Annuler</button>
          <button type="submit" class="profil-btn profil-btn-primary">Confirmer</button>
        </div>
      </form>
    </section>
  `;

  // Mise à jour dynamique de la force du nouveau mot de passe
  document.getElementById('new-password').addEventListener('input', function() {
    const password = this.value;
    const errorMessage = document.getElementById('password-change-error');
    
    // Si vous avez la bibliothèque zxcvbn, utilisez-la pour évaluer la force du mot de passe
    if (typeof zxcvbn === 'function') {
      const strength = zxcvbn(password);
      if (strength.score < 2) { // 0 ou 1 => mot de passe trop faible
        errorMessage.textContent = 'Mot de passe trop faible.';
      } else {
        errorMessage.textContent = '';
      }
    } else {
      // Vérification basique si zxcvbn n'est pas disponible
      if (password.length < 8) {
        errorMessage.textContent = 'Le mot de passe doit contenir au moins 8 caractères.';
      } else {
        errorMessage.textContent = '';
      }
    }
  });

  // Validation du formulaire de changement de mot de passe
  document.getElementById('change-password-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const oldPassword = document.getElementById('old-password').value;
    const newPassword = document.getElementById('new-password').value;
    const confirmNewPassword = document.getElementById('confirm-new-password').value;
    const errorMessage = document.getElementById('password-change-error');
    
    // Vérification si l'ancien mot de passe est rempli
    if (!oldPassword) {
      errorMessage.textContent = 'Veuillez saisir votre ancien mot de passe.';
      return;
    }
    
    // Vérification de la correspondance entre le nouveau mot de passe et sa confirmation
    if (newPassword !== confirmNewPassword) {
      errorMessage.textContent = 'Les nouveaux mots de passe ne correspondent pas.';
      return;
    }
    
    
    // Vérification que le nouveau mot de passe est différent de l'ancien
    if (newPassword === oldPassword) {
      errorMessage.textContent = 'Le nouveau mot de passe doit être différent de l\'ancien.';
      return;
    }
    
    // Préparer les données pour la requête
    const formData = new FormData();
    const hasholdPassword = CryptoJS.SHA256(oldpassword).toString(CryptoJS.enc.Hex);
    const hashnewPassword = CryptoJS.SHA256(newpassword).toString(CryptoJS.enc.Hex);
    formData.append('old_password', hasholdPassword);
    formData.append('new_password', hashnewPassword);
    
    // Envoyer la requête au serveur
    document.body.style.cursor = "wait";
    fetch('https://systeme-vote-backend-production.up.railway.app/api/modify-password.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === "success" && data.message === "Mot de passe modifié avec succès.") {
        errorMessage.textContent = "Mot de passe modifié avec succès.";
        document.body.style.cursor = "wait";

        setTimeout(() => {
          window.location.href = "profil";
        }, 3000);
      } else {
        errorMessage.textContent = data.message;
        document.body.style.cursor = "default";
      }
    })
    .catch(error => {
      errorMessage.textContent = 'Une erreur de communication avec le serveur est survenue.';
      console.error('Erreur');
    });
  });

  // Bouton d'annulation
  document.getElementById('cancel-password-change').addEventListener('click', restoreOriginalContent);

  // Fonction pour restaurer le contenu original
  function restoreOriginalContent() {
    mainElement.innerHTML = originalContent;
    
    // Réattacher l'événement au bouton de changement de mot de passe
    const changePasswordBtn = document.getElementById('password-change-container');
    if (changePasswordBtn) {
      changePasswordBtn.addEventListener('click', showChangePasswordForm);
    }
    
    // Réattacher l'événement au bouton de suppression de compte si nécessaire
    const deleteAccountBtn = document.getElementById('delete-account');
    if (deleteAccountBtn) {
      deleteAccountBtn.addEventListener('click', showDeleteAccountConfirmation);
    }
    
    // Réattacher l'événement au bouton de déconnexion si nécessaire
    const logoutBtn = document.getElementById('logout');
    if (logoutBtn) {
      // Vous pouvez ajouter ici la fonction de déconnexion
    }
  }

  // Toggle pour l'ancien mot de passe
  document.getElementById('toggle-old-password').addEventListener('click', function() {
    togglePasswordVisibility('old-password', this);
  });

  // Toggle pour le nouveau mot de passe
  document.getElementById('toggle-new-password').addEventListener('click', function() {
    togglePasswordVisibility('new-password', this);
  });

  // Toggle pour la confirmation du nouveau mot de passe
  document.getElementById('toggle-confirm-new-password').addEventListener('click', function() {
    togglePasswordVisibility('confirm-new-password', this);
  });

  // Fonction générique pour afficher/masquer un mot de passe
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

// Attacher l'événement au bouton de changement de mot de passe
document.addEventListener('DOMContentLoaded', function() {
const changePasswordBtn = document.getElementById('password-change-container');
if (changePasswordBtn) {
  changePasswordBtn.addEventListener('click', showChangePasswordForm);
} else {
  console.error("Le bouton de changement de mot de passe n'a pas été trouvé");
}
});