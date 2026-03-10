function retirerFavori(idMaison) {
  if (confirm("Retirer cette propriété de vos favoris ?")) {
    fetch("../PHP/toggle_favoris.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: "id_maison=" + idMaison,
    })
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "removed") {
          const card = document.getElementById("card-" + idMaison);
          card.classList.add("fade-out");

          setTimeout(() => {
            card.remove();
            // Mise à jour du compteur
            const countEl = document.getElementById("count-fav");
            let currentCount = parseInt(countEl.innerText);
            countEl.innerText = currentCount - 1;

            if (currentCount - 1 === 0) location.reload();
          }, 400);
        }
      });
  }
}
