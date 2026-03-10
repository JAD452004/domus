// Script AJAX universel pour navigation sans rechargement
// Placez ce script dans un fichier commun (ex: navigation.js) et incluez-le dans toutes vos pages

document.addEventListener("DOMContentLoaded", function () {
  // Ciblez tous les liens internes (hors #, mailto, tel, et liens externes)
  document.body.addEventListener("click", function (e) {
    const link = e.target.closest("a");
    if (
      link &&
      link.getAttribute("href") &&
      !link.getAttribute("href").startsWith("http") &&
      !link.getAttribute("href").startsWith("mailto:") &&
      !link.getAttribute("href").startsWith("tel:") &&
      link.getAttribute("href") !== "#"
    ) {
      e.preventDefault();
      const url = link.getAttribute("href");
      fetch(url)
        .then((response) => response.text())
        .then((html) => {
          // Remplacez #main-content par l'id de votre conteneur principal
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, "text/html");
          const newContent = doc.querySelector("#main-content") || doc.body;
          document.querySelector("#main-content").innerHTML =
            newContent.innerHTML;
          window.history.pushState({}, "", url);
        })
        .catch((err) => {
          alert("Erreur lors du chargement de la page.");
        });
    }
  });

  // Gestion du bouton retour navigateur
  window.addEventListener("popstate", function () {
    fetch(location.pathname)
      .then((response) => response.text())
      .then((html) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, "text/html");
        const newContent = doc.querySelector("#main-content") || doc.body;
        document.querySelector("#main-content").innerHTML =
          newContent.innerHTML;
      });
  });
});
