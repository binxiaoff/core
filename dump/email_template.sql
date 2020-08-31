INSERT INTO unilend.mail_footer (id, name, locale, content, archived, updated, added) VALUES (1, 'footer', 'fr_FR', '
  <mj-column>
      <mj-text font-weight="100" font-size="11px" line-height="1.5" align="center" padding-top="40px" color="#3F2865">
          Copyright © 2020 KLS, tous droits réservés.<br/>
          Où nous trouver : 50 rue la Boétie, 75008 Paris, France<br/>
      </mj-text>
  </mj-column>
', null, '2019-11-29 15:13:47', '2019-11-29 15:13:47');

INSERT INTO unilend.mail_header (id, name, locale, content, archived, updated, added) VALUES (1, 'header', 'fr_FR', '
  <mj-column><mj-image align="left" width="60px" src="{{ url("front_image", {imageFileName: "emails/logo.png"}) }}"/></mj-column>
  <mj-column>
      <mj-text align="right" color="#ffffff" font-size="10px" font-family="Arial" line-height="50px">
          FINANCER MIEUX, FINANCER ENSEMBLE
      </mj-text>
  </mj-column>
', null, '2019-11-29 15:13:47', '2019-11-29 15:13:47');

INSERT INTO unilend.mail_layout (id, name, locale, content, archived, updated, added) VALUES
(1, 'layout', 'fr_FR', '
      <mjml>
        <mj-head>
            <mj-style>
                a { color: #3F2865; font-weight: 600; text-decoration: none; }
            </mj-style>
        </mj-head>
        <mj-body>
            <mj-section background-color="#3F2865" padding="0">
                {% block header %}{% endblock %}
            </mj-section>
            <mj-section padding="0">
                <mj-column width="560px" background-color="#F5F4F7" padding="0 40px 30px">
                    {% block body %}{% endblock %}
                </mj-column>
            </mj-section>
            <mj-section>
                {% block footer %}{% endblock %}
            </mj-section>
        </mj-body>
    </mjml>
', null, null, '2019-11-29 15:13:47'),
(2, 'internal', 'fr_FR', '      <mjml>
        <mj-style>
        html {
          color: black;
        }
</mj-style>
        <mj-body>
            <mj-section>
                {% block body %}{% endblock %}
            </mj-section>
        </mj-body>
    </mjml>', null, null, '2020-04-06 10:28:50');

INSERT INTO unilend.mail_template (id, id_header, id_footer, id_layout, name, locale, content, archived, subject, sender_name, sender_email, updated, added)
VALUES
(23, 1, 1, 1, 'staff-client-initialisation', 'fr_FR', '{% set inscriptionFinalisationUrl = url("front_inscription_finalisation", {temporaryTokenHash: temporaryToken.token, clientHash: client.hash }) %}
<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/fireworks.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour,</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
   Votre compte, {{ staff.roles ? "en tant que " ~ staff.roles|join(", ") }} {{ staff.marketSegments ? "sur le(s) marché(s) " ~ staff.marketSegments|join(", ") }} sur la plateforme KLS, a été créé.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Nous vous invitons à aller compléter votre profil directement sur <a href="{{ inscriptionFinalisationUrl }}">{{ "kls-platform.com" }}</a>.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ inscriptionFinalisationUrl }}">
    Compléter le profil
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions,
    l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - Initialisation de votre compte', 'KLS', 'support@kls-platform.com', null, '2020-01-29 13:58:22'),
(24, 1, 1, 1, 'client-password-request', 'fr_FR', '
            <mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/reload.png"}) }}"/>
            <mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour {{ client.firstName }},</mj-text>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
                Vous avez demandé à réinitialiser votre mot de passe sur la plateforme KLS.
            </mj-text>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
                Pour cela, cliquez sur le lien ci-dessous. La durée de validité de ce lien est de 24h. Au-delà, merci de bien vouloir reformuler votre demande.
            </mj-text>
            <mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px"
                       href="{{ url("front_password_change", {temporaryTokenHash: temporaryToken.token, clientHash: client.hash}) }}">
                Réinitialiser le mot de passe
            </mj-button>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
                Si vous n’avez pas demandé à réinitialiser votre mot de passe ou pour toute question, merci de contacter le support client KLS (<a href="mailto:support@kls-platform.com">support@kls-platform.com</a>)
            </mj-text>
            <mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>
        ', null, 'KLS – votre nouveau mot de passe', 'KLS', 'support@kls-platform.com', null, '2020-01-29 14:43:51'),
(25, 1, 1, 1, 'publication-prospect-company', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ submitterCompany.name }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.name }}, sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre Établissement n’étant pas encore adhérent à la plateforme, nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une demande de marque d’intérêt !', 'KLS', 'support@kls-platform.com', null, '2020-01-31 12:25:05'),
(26, 1, 1, 1, 'publication-uninitialized-user', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ submitterCompany.name }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.name }}.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre compte n’étant pas encore créé sur la plateforme d’aide à la syndication, KLS.
    Nous vous invitons à créer votre compte dès maintenant pour accéder au dossier.
    Votre demande d’habilitation sera transmise à votre responsable pour qu’il valide votre accès.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ url("front_inscription_finalisation", {temporaryTokenHash: temporaryToken.token, clientHash: client.hash }) }}">
    Créer mon compte
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une demande de marque d’intérêt !', 'KLS', 'support@kls-platform.com', null, '2020-01-31 12:25:05'),
(27, 1, 1, 1, 'publication', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/book.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ submitterCompany.name }} vous invite à marquer votre intérêt sur la participation de votre Établissement au financement du dossier {{ project.name }}.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px"
href="{{ url("front_participation_project_view", {projectHash : project.hash}) }}">
    Consulter l’invitation
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une demande de marque d’intérêt !', 'KLS', 'support@kls-platform.com', null, '2020-01-31 12:25:05'),
(28, 1, 1, 1, 'syndication-prospect-company', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ submitterCompany.name }} vous invite à participer au financement du dossier {{ project.name }}, sur la plateforme KLS, l’outil d’aide à la syndication, accessible sur <a href="{{ url("front_home") }}">www.kls-platform.com</a>.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre Établissement n’étant pas encore adhérent à la plateforme, nous vous invitons à prendre contact auprès de <a href="mailto:cecile.joly@ca-lendingservices.com">cecile.joly@ca-lendingservices.com</a> ou de <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> afin de pouvoir rapidement formaliser votre adhésion et avoir accès au dossier.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une invitation !', 'KLS', 'support@kls-platform.com', null, '2020-01-31 12:25:05'),
(29, 1, 1, 1, 'syndication-uninitialized-user', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ submitterCompany.name }} vous invite à participer au financement sur la participation de votre Établissement au financement du dossier {{ project.name }}.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Votre compte n’étant pas encore créé sur la plateforme d’aide à la syndication, KLS.
    Nous vous invitons à créer votre compte dès maintenant pour accéder au dossier.
    Votre demande d’habilitation sera transmise à votre responsable pour qu’il valide votre accès.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ url("front_inscription_finalisation", {temporaryTokenHash: temporaryToken.token, clientHash: client.hash }) }}">
    Créer mon compte
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une invitation !', 'KLS', 'support@kls-platform.com', null, '2020-01-31 12:25:05'),
(30, 1, 1, 1, 'syndication', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/plant.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour{{ client.firstName ? " " ~ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ submitterCompany.name }} vous invite à participer au financement du dossier {{ project.name }}.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ url("front_participation_project_view", {projectHash : project.hash}) }}">
    Consultez l’invitation
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a> ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS - vous avez reçu une invitation !', 'KLS', 'support@kls-platform.com', null, '2020-01-31 12:25:05'),
(31, 1, 1, 1, 'project-file-uploaded', 'fr_FR', '<mj-image align="right" padding="0 0 0 0 " width="60px" src="{{ url("front_image", {imageFileName: "emails/attachment-uploaded.png"}) }}"/>
<mj-text color="#3F2865" font-size="22px" font-weight="700">Bonjour {{ client.firstName }},</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    {{ project.submitterCompany }} vient de charger un nouveau document sur le dossier {{ project.title }} dans la plateforme KLS.
</mj-text>
<mj-button background-color="#F9B13B" border-radius="99px" font-weight="500" inner-padding="7px 30px" href="{{ url("front_participation_project_view", {projectHash: project.hash}) }}">
    Consulter sur KLS
</mj-button>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">
    Pour vous accompagner dans l’utilisation de la plateforme et répondre à vos questions, l’équipe support de KLS est à votre disposition du lundi au vendredi de 9h à 18h à l’adresse <a href="mailto:support@kls-platform.com">support@kls-platform.com</a>) ou via le live-chat disponible sur la plateforme.
</mj-text>
<mj-text color="#3F2865" font-size="14px" font-weight="100" align="justify" line-height="1.5">L’équipe KLS</mj-text>', null, 'KLS – Nouveau document sur le dossier {{ project.title }}', 'KLS', 'support@kls-platform.com', null, '2020-01-31 12:25:05'),
(32, null, 1, 2, 'log', 'fr_FR', '  <mj-column>
    <mj-text><h1>{{ message }}</h1></mj-text>
    {% if datetime is defined %}
    <mj-text color="gray">{{ datetime|date(''c'')}}</mj-text>
    {% endif %}
    {% if context is defined and context is not empty %}
    <mj-text><h2>Context</h2></mj-text>
      {% for key, value in context %}
        <mj-text><strong>{{ key }}</strong></mj-text>
        <mj-text><pre>{{ value }}</pre></mj-text>
        {% if loop.last is same as(false) %}
        <mj-divider border-width="1px" border-style="dashed" border-color="lightgrey"/>
        {% endif %}
      {% endfor %}
    {% endif %}
    <mj-divider border-width="3px" border-style="solid" border-color="black" />
    {% if extra is defined and extra is not empty %}
    <mj-text><h2>Extra</h2></mj-text>
      {% for key, value in extra %}
        <mj-text><strong>{{ key }}</strong></mj-text>
        <mj-text><pre>{{ value }}</pre></mj-text>
        {% if loop.last is same as(false) %}
        <mj-divider border-width="1px" border-style="dashed" border-color="lightgrey"/>
        {% endif %}
      {% endfor %}
    {% endif %}
  </mj-column>', null, '{{ level_name }} on KLS {{ environment|default(false) ? ''in '' ~ environment }}', 'KLS', 'noreply@kls-platform.com', null, '2020-04-06 10:28:50');
