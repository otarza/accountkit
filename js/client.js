console.log("Account Kit client loaded");
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

if (document.getElementById("sms-login-submit")) {
  document.getElementById("sms-login-submit").addEventListener("click", function (e) {
    e.preventDefault()
    smsLogin();
    return false;
  });
}

if (document.getElementById("email-login-submit")) {
  document.getElementById("email-login-submit").addEventListener("click", function (e) {
    e.preventDefault()
    emailLogin();
    return false;
  });
}

// login callback
function loginCallback(response) {
  if (response.status === "PARTIALLY_AUTHENTICATED") {
    // Set the code and submit the form.
    document.getElementById("code").value = response.code;
    document.getElementById("accountkit-login-form").submit();
  }
  else if (response.status === "NOT_AUTHENTICATED") {
    // handle authentication failure
    console.log(response);
  }
  else if (response.status === "BAD_PARAMS") {
    // handle bad parameters
    console.log(response);
  }
}

// phone form submission handler
function smsLogin() {
  console.log("smsLogin");

  var countryCode = document.getElementById("edit-country-code").value;
  var phoneNumber = document.getElementById("edit-phone-number").value;
  AccountKit.login(
    'PHONE',
    {countryCode: countryCode, phoneNumber: phoneNumber}, // will use default values if not specified
    loginCallback
  );
}


// email form submission handler
function emailLogin() {
  console.log("emailLogin");

  var emailAddress = document.getElementById("edit-email").value;
  AccountKit.login(
    'EMAIL',
    {emailAddress: emailAddress},
    loginCallback
  );
}



