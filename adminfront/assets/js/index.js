const loginForm = document.getElementById("loginform")

console.log(API_DOMAIN)

loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    if (loginForm.email.value.trim() == "" || loginForm.pword.value.trim() == "") {
      loginForm.reset();
      alert("Please the number of Tokens you want to generate")
      return;
    }

    const res = await fetch(`${API_DOMAIN}/api/pilot/login`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({email: loginForm.email.value, password: loginForm.pword.value})
    });

    // console.log(res)

    if (res.ok) {
        const data = await res.json();

        if (data.result) {
            console.log(data)
          alert(data.message)

          // save session keys to localstorage, sessionstorage and cookies

          // Redirect to dashboard
          location.href = "./dashboard.html";
        }
        else {
            alert(data.message)
        }
       
    } else {
      alert('Error: Login failed (Response not json)');
    }
  });
