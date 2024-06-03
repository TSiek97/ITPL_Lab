</div>


</div>

<hr>

<div id='footer-wrapper'>
<?php include 'templates/footer.php'?>
</div>

</div>
<script>
function toggleCheckbox(checkboxId, contentId) {
    var checkbox = document.getElementById(checkboxId);
    var content = document.getElementById(contentId);
    checkbox.checked = !checkbox.checked;
    content.style.display = checkbox.checked ? "block" : "none";
  }

    function loadContent(page) {
        var xhttp = new XMLHttpRequest();
        xhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200) {
                document.getElementById("content-container").innerHTML = this.responseText;
            }
        };
        xhttp.open("GET", page, true);
        xhttp.send();
    }

    // Load initial content when the page loads
    document.addEventListener("DOMContentLoaded", function() {
        loadContent('templates/produkte.php');
    });
      


    document.getElementById("filterButton").addEventListener("click", function() {
        var filterCard = document.getElementById("filterCard");
        if (filterCard.style.display === "block") {
            filterCard.style.display = "none";
        } else {
            filterCard.style.display = "block";
        }
        });

    document.getElementById("filterForm").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent form submission
    console.log("Filters applied!");
    });

    document.getElementById("filterButton").addEventListener("click", function() {
        var filterCard = document.getElementById("filterCard");
        var backdrop = document.getElementById("backdrop");
        filterCard.style.display = "block";
        backdrop.style.display = "block";
    }); 

    document.getElementById("filterForm").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent form submission
    var filterCard = document.getElementById("filterCard");
    var backdrop = document.getElementById("backdrop");
    filterCard.style.display = "none";
    backdrop.style.display = "none";
    console.log("Filters applied!");
    });

    document.getElementById("backdrop").addEventListener("click", function() {
    var filterCard = document.getElementById("filterCard");
    var backdrop = document.getElementById("backdrop");
    filterCard.style.display = "none";
    backdrop.style.display = "none";
    });



</script>
</body>
</html>
