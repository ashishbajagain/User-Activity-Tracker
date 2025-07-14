
document.addEventListener("DOMContentLoaded", function () {
  if (typeof tracker_object !== 'undefined' && tracker_object.environment === 'production') {
    const throttleKey = 'lastTrackingTime';
    const lastSent = parseInt(localStorage.getItem(throttleKey), 10) || 0;
    const now = Date.now();
    if (/bot|crawl|spider|headless/i.test(navigator.userAgent)) return;
    if (now - lastSent < 60000) return;

    const startTime = new Date().toISOString();
    localStorage.setItem('startTime', startTime);
    let userLocation = 'unknown';
    let sent = false;

    function getBrowserName() {
      const ua = navigator.userAgent;
      if (/edg/i.test(ua)) return 'Edge';
      if (/chrome|crios/i.test(ua)) return 'Chrome';
      if (/firefox/i.test(ua)) return 'Firefox';
      if (/safari/i.test(ua) && !/chrome|crios/i.test(ua)) return 'Safari';
      return 'Unknown';
    }

    function sendData() {
      if (sent) return;
      const endTime = new Date().toISOString();
      const duration = Math.round((new Date(endTime) - new Date(startTime)) / 1000);
      if (duration <= 0) return;

      const payload = {
        screenWidth: screen.width,
        screenHeight: screen.height,
        language: navigator.language,
        timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
        cookiesEnabled: navigator.cookieEnabled,
        pageTitle: document.title,
        location: userLocation,
        operatingSystem: navigator.platform,
        startTime: startTime,
        endTime: endTime,
        timeSpent: duration,
        browser: getBrowserName(),
        userAgent: navigator.userAgent
      };

      fetch('/wp-json/tracker/v1/log/', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        keepalive: true
      });

      localStorage.setItem(throttleKey, Date.now());
      sent = true;
    }

    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        pos => {
          userLocation = `${pos.coords.latitude}, ${pos.coords.longitude}`;
        },
        () => {}
      );
    }

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) sendData();
    });

    window.addEventListener('beforeunload', sendData);
    window.addEventListener('unload', sendData);
    if (/mobile|android|iphone/i.test(navigator.userAgent)) {
      window.addEventListener('pagehide', sendData);
    }
  }
});
