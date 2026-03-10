/* 
   Fichier: script.js
   Auteur: [Votre Nom]
   Date: [Date de création]
   Description: Fonctionnalités JavaScript pour le site DOMUS Real Estate
   Version: 1.0
*/

// ============================
// GESTION DES FAVORIS
// ============================

/**
 * Fonction pour ajouter/supprimer une propriété des favoris
 * @param {number} idMaison - ID de la propriété
 * @param {HTMLElement} btn - Bouton favori cliqué
 */
function toggleFavori(idMaison, btn) {
  // Bascule la classe active pour l'effet visuel immédiat
  btn.classList.toggle("active");
  const icon = btn.querySelector("i");

  // Changement d'icône selon l'état
  if (btn.classList.contains("active")) {
    icon.classList.remove("far", "fa-heart");
    icon.classList.add("fas", "fa-heart");
    icon.style.color = "#ef4444"; // Coeur rouge
  } else {
    icon.classList.remove("fas", "fa-heart");
    icon.classList.add("far", "fa-heart");
    icon.style.color = "";
  }

  // Envoi de la requête AJAX pour sauvegarder en base de données
  fetch("../PHP/toggle_favoris.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/x-www-form-urlencoded",
    },
    body: `id_maison=${idMaison}`,
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Favori mis à jour:", data);
      // Ici vous pourriez ajouter des notifications
    })
    .catch((error) => {
      console.error("Erreur:", error);
      // En cas d'erreur, on annule visuellement
      btn.classList.toggle("active");
      if (btn.classList.contains("active")) {
        icon.classList.remove("far", "fa-heart");
        icon.classList.add("fas", "fa-heart");
        icon.style.color = "#ef4444";
      } else {
        icon.classList.remove("fas", "fa-heart");
        icon.classList.add("far", "fa-heart");
        icon.style.color = "";
      }
    });
}

// ============================
// ANIMATIONS AU DÉFILEMENT
// ============================

/**
 * Configuration de l'Intersection Observer
 * Pour animer les éléments quand ils entrent dans la vue
 */
const observerOptions = {
  threshold: 0.1, // Déclenche quand 10% de l'élément est visible
  rootMargin: "0px 0px -50px 0px", // Décalage vers le haut
};

const observer = new IntersectionObserver((entries) => {
  entries.forEach((entry) => {
    if (entry.isIntersecting) {
      // Ajoute l'animation quand l'élément est visible
      entry.target.style.animation = "fadeInUp 0.8s ease-out forwards";
    }
  });
}, observerOptions);

// Application de l'observer sur les cartes
document
  .querySelectorAll(".property-card, .feature-card, .city-card")
  .forEach((el) => {
    observer.observe(el);
  });

// ============================
// DÉFILEMENT DOUX POUR LES LIENS D'ANCRE
// ============================

/**
 * Gestion du défilement doux pour les liens internes
 */
document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener("click", function (e) {
    const targetId = this.getAttribute("href");

    // Ignore les liens vers "#"
    if (targetId === "#") return;

    const targetElement = document.querySelector(targetId);
    if (targetElement) {
      e.preventDefault(); // Empêche le comportement par défaut

      // Défilement doux vers l'élément cible
      window.scrollTo({
        top: targetElement.offsetTop - 100, // Ajustement pour la navbar
        behavior: "smooth",
      });
    }
  });
});

// ============================
// VALIDATION DU FORMULAIRE DE RECHERCHE
// ============================

/**
 * Validation du formulaire de recherche
 * Vérifie que le prix minimum n'est pas supérieur au prix maximum
 */
const searchForm = document.querySelector(".search-wrapper form");
if (searchForm) {
  searchForm.addEventListener("submit", function (e) {
    const prixMin = document.querySelector('input[name="prix_min"]');
    const prixMax = document.querySelector('input[name="prix_max"]');

    if (
      prixMin &&
      prixMax &&
      prixMin.value &&
      prixMax.value &&
      parseInt(prixMin.value) > parseInt(prixMax.value)
    ) {
      e.preventDefault();
      alert("Le prix minimum ne peut pas être supérieur au prix maximum.");
      prixMin.focus();
    }
  });
}

// ============================
// FORMATAGE DES PRIX
// ============================

/**
 * Formatage automatique des champs de prix
 * Supprime les caractères non numériques
 */
document.querySelectorAll('input[type="number"]').forEach((input) => {
  input.addEventListener("input", function () {
    if (this.value) {
      this.value = this.value.replace(/\D/g, ""); // Garde uniquement les chiffres
    }
  });
});

// ============================
// INITIALISATION AU CHARGEMENT
// ============================

/**
 * Fonction d'initialisation exécutée au chargement de la page
 */
document.addEventListener("DOMContentLoaded", function () {
  console.log("Site DOMUS chargé avec succès");

  // Ici vous pourriez ajouter d'autres initialisations
  // Exemple: Chargement des favoris depuis le localStorage
  // loadFavoritesFromStorage();
});

// ============================
// FONCTIONS UTILITAIRES
// ============================

/**
 * Formate un prix avec des espaces pour la lisibilité
 * @param {number} price - Prix à formater
 * @returns {string} Prix formaté
 */
function formatPrice(price) {
  return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
}

/**
 * Met en majuscule la première lettre d'une chaîne
 * @param {string} str - Chaîne à formater
 * @returns {string} Chaîne formatée
 */
function capitalizeFirstLetter(str) {
  return str.charAt(0).toUpperCase() + str.slice(1).toLowerCase();
}

// ============================
// GESTION DES ERREURS
// ============================

/**
 * Gestionnaire d'erreurs global
 * Affiche les erreurs dans la console et potentiellement à l'utilisateur
 */
window.onerror = function (message, source, lineno, colno, error) {
  console.error("Erreur JavaScript:", {
    message: message,
    source: source,
    ligne: lineno,
    colonne: colno,
    erreur: error,
  });

  // Vous pourriez ici envoyer les erreurs à un service de tracking
  // ou afficher un message à l'utilisateur
  return false;
};

// ============================
// DÉTECTION DES FONCTIONNALITÉS DU NAVIGATEUR
// ============================

/**
 * Vérifie si le navigateur supporte certaines fonctionnalités
 */
function checkBrowserFeatures() {
  const features = {
    intersectionObserver: "IntersectionObserver" in window,
    fetch: "fetch" in window,
    promise: "Promise" in window,
  };

  // Log pour le débogage
  console.log("Fonctionnalités supportées:", features);

  // Message d'avertissement si fetch n'est pas supporté
  if (!features.fetch) {
    console.warn(
      "Votre navigateur ne supporte pas Fetch API. Certaines fonctionnalités seront limitées.",
    );
  }
}

// Exécute la vérification au chargement
checkBrowserFeatures();
