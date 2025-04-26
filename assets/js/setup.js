let     API_DOMAIN = "https://digify.myf2.net";

if (location.hostname == "localhost" || location.hostname == "127.0.0.1") {
    API_DOMAIN = "http://localhost/digify";
}