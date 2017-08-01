// initialize Account Kit with CSRF protection
AccountKit_OnInteractive = function () {
  AccountKit.init(
    {
      appId: drupalSettings.accountkit.client.app_id,
      state: "{{'account-kit'}}",
      version: drupalSettings.accountkit.client.api_version,
      fbAppEventsEnabled: true,
      debug: true
    }
  );
};


document.getElementById("edit-sms-login").addEventListener("click", function (e) {
  e.preventDefault()
  smsLogin();
  return false;
});

// login callback
function loginCallback(response) {
  console.log("loginCallback");
  if (response.status === "PARTIALLY_AUTHENTICATED") {
    var code = response.code;
    var csrf = response.state;

    document.getElementById("code").value = response.code;
    document.getElementById("csrf").value = response.state;
    document.getElementById("login_success").submit();
    // Send code to server to exchange for access token
  }
  else if (response.status === "NOT_AUTHENTICATED") {
    // handle authentication failure
  }
  else if (response.status === "BAD_PARAMS") {
    // handle bad parameters
  }
}

// phone form submission handler
function smsLogin() {
  console.log("smsLogin");

  var countryCode = document.getElementById("country_code").value;
  var phoneNumber = document.getElementById("phone_number").value;
  AccountKit.login(
    'PHONE',
    {countryCode: countryCode, phoneNumber: phoneNumber}, // will use default values if not specified
    loginCallback
  );
}


// email form submission handler
function emailLogin() {
  console.log("emailLogin");

  var emailAddress = document.getElementById("email").value;
  AccountKit.login(
    'EMAIL',
    {emailAddress: emailAddress},
    loginCallback
  );
}



