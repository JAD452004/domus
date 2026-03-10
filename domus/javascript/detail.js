function updateImg(url, el) {
  const mainView = document.getElementById("main-view");

  // Effet de transition douce
  mainView.style.opacity = "0";

  setTimeout(() => {
    mainView.src = url;
    mainView.style.opacity = "1";
  }, 200);

  // Mise à jour des miniatures
  document
    .querySelectorAll(".thumb")
    .forEach((t) => t.classList.remove("active"));
  el.classList.add("active");
}
