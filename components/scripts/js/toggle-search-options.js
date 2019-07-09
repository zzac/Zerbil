function toggleSearchOptions() {
    var searchOptionsDiv = document.getElementById("search-options");
    var searchBarDiv = document.getElementById("search-bar");

    if (getComputedStyle(document.getElementById("search-options"), null).display == "none") {
        searchOptionsDiv.style.display = "block";
        searchBarDiv.style.borderBottom = "1px solid #4022b9";
        searchBarDiv.style.marginBottom = "20px";
    } else {
        searchOptionsDiv.style.display = searchBarDiv.style.borderBottom = "none";
        searchBarDiv.style.marginBottom = "0";
    }
}
