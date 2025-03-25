document.addEventListener("DOMContentLoaded", function () {
    const modal = document.getElementById("modal");
    const modalTitle = document.getElementById("modalTitle");
    const modalText = document.getElementById("modalText");
    const closeModal = document.getElementById("closeModal");

    // Dictionnaire des descriptions pour chaque vote-card
    const descriptions = {
        "Ville Solaire": "Ce projet propose d'installer des panneaux solaires sur les toits des bâtiments publics afin de réduire la dépendance aux énergies fossiles et de favoriser la production d'énergie renouvelable. L'objectif est de maximiser l'autoconsommation énergétique des bâtiments, de réduire la facture énergétique des collectivités locales et d'augmenter l'approvisionnement en électricité verte. En plus de la réduction des coûts, ces installations contribuent à la transition énergétique, diminuent l'empreinte carbone et participent à la sensibilisation des citoyens à l'importance des énergies renouvelables.",
        "Mobilité Verte": "Ce thème vise à promouvoir une mobilité plus durable en développant un réseau dense de pistes cyclables sécurisées et accessibles, tout en augmentant le nombre de vélos en libre-service. L'objectif est de réduire la pollution et la congestion des rues en incitant les habitants à privilégier des modes de transport doux et non polluants, comme le vélo. Les infrastructures adaptées à la pratique du vélo, couplées à un système de vélos en libre-service, facilitent la transition vers une ville plus verte et plus respirable, tout en réduisant les émissions de CO₂.",
        "Forêts Urbaines": "Ce projet propose de créer des espaces verts en milieu urbain sous forme de mini-forêts, inspirées du concept des 'forêts urbaines'. Ces petites forêts permettront non seulement d'améliorer la qualité de l'air en absorbant le CO₂ et autres polluants, mais aussi de lutter contre les îlots de chaleur urbains. Elles offriront des zones de détente et de biodiversité, contribuant à améliorer le bien-être des habitants tout en participant à l'équilibre écologique de la ville. L'intégration de la nature en ville devient ainsi un levier majeur pour rendre les espaces urbains plus résilients face au changement climatique.",
        "Ville Zéro Gaspillage": "Ce thème aborde la gestion de l'eau de manière circulaire, en visant une ville où la réutilisation des eaux usées devient la norme. Cela inclut la collecte et le traitement des eaux grises pour des usages non alimentaires, comme l'irrigation des espaces verts ou le nettoyage des rues. Le projet comprend également la mise en place de systèmes pour réduire la consommation d'eau potable, en intégrant des technologies comme les économiseurs d'eau et la récupération de pluie. En réduisant le gaspillage et en réutilisant l'eau, la ville pourra faire face à la rareté de l'eau, protéger ses ressources naturelles et réduire ses coûts d'approvisionnement.",
        "Bâtiments Intelligents": "L'idée de ce projet est de construire des bâtiments autonomes qui génèrent leur propre énergie grâce à des sources renouvelables (solaire, éolien, géothermie), tout en étant conçus à partir de matériaux recyclés et à faible impact environnemental. Les maisons intelligentes intègrent des systèmes de gestion de l'énergie permettant d'optimiser la consommation en temps réel. Ce concept vise à réduire les besoins énergétiques externes, à minimiser l'empreinte écologique de la construction et à offrir aux habitants un cadre de vie plus durable, tout en contribuant à la neutralité carbone de la ville.",
        "Déchets = Ressources": "Ce projet vise à transformer la gestion des déchets en une ressource précieuse grâce à un recyclage efficace et une politique de compostage obligatoire pour tous les citoyens. Les déchets organiques sont compostés pour produire de l'engrais, tandis que les matériaux recyclables (plastiques, métaux, papiers) sont traités pour être réutilisés dans de nouveaux produits. Ce système de gestion circulaire permet de réduire les déchets envoyés en décharge et de favoriser une économie circulaire où les matières premières sont constamment réutilisées. L'objectif est de faire de chaque déchet une ressource pour une ville plus propre, plus verte et plus durable."
    };

    // Ouvre la modale lors du clic sur une carte
    document.querySelectorAll(".vote-card").forEach(card => {
        card.addEventListener("click", () => {
            const title = card.querySelector(".vote-title").textContent;
            modalTitle.textContent = title;
            modalText.textContent = descriptions[title] || "Description non disponible.";

            modal.classList.add("active");
        });
    });

    // Ferme la modale en cliquant sur le bouton ou à l'extérieur
    closeModal.addEventListener("click", () => modal.classList.remove("active"));
    modal.addEventListener("click", (e) => {
        if (e.target === modal) {
            modal.classList.remove("active");
        }
    });
});
