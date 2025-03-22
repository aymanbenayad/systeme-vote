// Sélection des éléments
const hero = document.querySelector('.hero');
const signupBtn = document.getElementById('signup-btn');
const loginLink = document.getElementById('login-link');

// Fonction pour afficher le formulaire d'inscription
function showSignupForm() {
  hero.innerHTML = `
    <h2 class="form-title">Inscription</h2>
    <form id="signup-form">
      <input type="text" placeholder="Nom" required>
      <input type="email" placeholder="Email" required>
      <input type="password" placeholder="Mot de passe" required>
      <button type="submit">S'inscrire</button>
    </form>
    <p class="switch-form">Déjà un compte ? <a href="#" id="switch-to-login">Se connecter</a></p>
  `;

  // Ajouter un écouteur d'événement pour basculer vers le formulaire de connexion
  document.getElementById('switch-to-login').addEventListener('click', showLoginForm);
}

// Fonction pour afficher le formulaire de connexion
function showLoginForm() {
  hero.innerHTML = `
    <h2 class="form-title">Connexion</h2>
    <form id="login-form">
      <input type="email" placeholder="Email" required>
      <input type="password" placeholder="Mot de passe" required>
      <button type="submit">Se connecter</button>
    </form>
    <p class="switch-form">Pas de compte ? <a href="#" id="switch-to-signup">S'inscrire</a></p>
  `;

  // Ajouter un écouteur d'événement pour basculer vers le formulaire d'inscription
  document.getElementById('switch-to-signup').addEventListener('click', showSignupForm);
}

// Écouteurs d'événements pour les boutons
signupBtn.addEventListener('click', showSignupForm);
loginLink.addEventListener('click', (e) => {
  e.preventDefault(); // Empêcher le comportement par défaut du lien
  showLoginForm();
});