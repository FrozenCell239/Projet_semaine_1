let links = document.querySelectorAll("[data-delete]");

for(let link of links){
    link.addEventListener("click", function(e){
        e.preventDefault(); //Prevents navigation.

        if(confirm("Êtes-vous sûr(e) de vouloir supprimer cette image ? Cette action est irréversible.")){
            // Send AJAX request
            fetch(this.getAttribute("href"), {
                method: "DELETE",
                headers: {
                    "X-Requested-With": "XMLHttpRequest",
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({"_token": this.dataset.token})
            })
            .then(response => response.json())
            .then(data => {
                if(data.success){
                    this.parentElement.remove();
                }
                else{alert(data.error);};
            })
        };
    })
};