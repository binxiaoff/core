============
Postman
============

`Postman <https://www.getpostman.com/>`_ is a useful REST client. It useful for testing the API without a frontend.

Add the KLS request collection
==============================
Postman can read the `OpenApi <https://www.openapis.org/>`_ specification. It defines a standard, language-agnostic interface to RESTful APIs which allows both humans and computers to discover and understand the capabilities of the service without access to source code, documentation, or through network traffic inspection.

The current API documentation for our project is available at https://api.local.kls-platform.com/ .
To import it in Postman as a request collection we need it in json format. It is available at
https://api.local.kls-platform.com/docs.json .

You can import it in Postman by clicking the import button. You can either download the file or paste the raw data from the previous link.
Unfortunately, we can't import it using "Import from Link".

If you already have a collection named "KLS", a prompt will propose you to either replace (meaning you will lose the previous collection) or
import it as a copy (keeping the conflicting collection).

Unfortunately, it is impossible to simply update a collection. Should the collection change, you will need to fully import it again.

Create an environment
=====================

Creating an environment is highly encouraged to store common data such as the base url and the various token you used in the application.
For example, with an environment you can automatically populate the authentication token in a variable then use it in following API calls.

Authentication
--------------

In the authentication call, "Test" section, paste the following snippet:

.. code-block:: js

    function jwt_decode(jwt) {
        var parts = jwt.split('.'); // header, payload, signature
        return JSON.parse(atob(parts[1]));
    }

    for (let key in pm.environment.toObject()) {
        if (key.startsWith('company.')) {
            pm.environment.unset(key);
        }
    }

    pm.environment.set("jwt", responseBody);

    const baseUrl = pm.environment.get('baseUrl') || 'https://api.local.kls-platform.com';

    let json = JSON.parse(responseBody);

    if (json.tokens) {
        pm.environment.set("tokens", json.tokens);
        const token = json.tokens[0];
        const { user } = jwt_decode(token);
        pm.environment.set('user', user);
        const userRequest = {
            url: `${baseUrl}${user}`,
            header: `Authorization: Bearer ${token}`
        }
        const tokens = json.tokens;
        pm.sendRequest(userRequest, function (err, response) {
            const { staff: userStaffs } = response.json()

            tokens.forEach((token) => {
                const { staff : staffIri } = jwt_decode(token)
                if (staffIri === undefined) {
                    pm.environment.set('userToken', token)
                } else {
                    const a = userStaffs.find((s) => s['@id'] === staffIri)
                    pm.environment.set("company." + a.company.shortCode, token);
                    pm.environment.set("company." + a.company.displayName, token);
                }
            })
        });
    }

    pm.environment.set("refreshToken", json.refresh_token);


Now when you execute this request, Postman will record variables related to authentication.
These variables are based on the available staff you have with the credentials you have given. Besides the variables containing the response (in the jwt variable);
the tokens array and the refresh token, you will have a variable for each en entity named after its short code containted in the payloads present in the tokens array.

You can now edit the collection (right click the collection name and select "Edit") to add a default authorization.
Select the "Authorization" tab, then "Bearer Token" in the type select input and finally enter the short of your default entity between "{{" and "}}" in the "Token"
field.

From this moment, when your request need authorization, you can simply select "Inherit from parent".

Refresh
-------

In the refresh token call, set the body parameter "refresh_token" to {{refreshToken}}.

Like in the authentication call, add this code block in the "Test" section of the reset call:

.. code-block:: js

    function jwt_decode(jwt) {
        var parts = jwt.split('.'); // header, payload, signature
        return JSON.parse(atob(parts[1]));
    }

    for (let key in pm.environment.toObject()) {
        if (key.startsWith('company.')) {
            pm.environment.unset(key);
        }
    }

    pm.environment.set("jwt", responseBody);

    const baseUrl = pm.environment.get('baseUrl') || 'https://api.local.kls-platform.com';

    let json = JSON.parse(responseBody);

    if (json.tokens) {
        pm.environment.set("tokens", json.tokens);
        const token = json.tokens[0];
        const { user } = jwt_decode(token);
        pm.environment.set('user', user);
        const userRequest = {
            url: `${baseUrl}${user}`,
            header: `Authorization: Bearer ${token}`
        }
        const tokens = json.tokens;
        pm.sendRequest(userRequest, function (err, response) {
            const { staff: userStaffs } = response.json()

            tokens.forEach((token) => {
                const { staff : staffIri } = jwt_decode(token)
                if (staffIri === undefined) {
                    pm.environment.set('userToken', token)
                } else {
                    const a = userStaffs.find((s) => s['@id'] === staffIri)
                    pm.environment.set("company." + a.company.shortCode, token);
                    pm.environment.set("company." + a.company.displayName, token);
                }
            })
        });
    }

    pm.environment.set("refreshToken", json.refresh_token);

Now, when you refresh the token, the variables will be updated with the result of the call enabling you to repeat them without manually entering the tokens.
These variables are based on the available staff you have with the credentials you have given. Besides the variables containing the response (in the jwt variable);
the tokens array and the refresh token, you will have a variable for each en entity named after its short code containted in the payloads present in the tokens array.


