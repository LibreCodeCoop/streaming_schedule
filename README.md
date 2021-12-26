# Streaming Schedule

Nextcloud OCC command to schedule videos on YouTube

## Install

* Clone this repository on your Nextcloud app folder and rum `composer install` to install all dependencies.
* Install and enable the app.

## Available command

```
 streamingscheduling
  streamingscheduling:config-youtube     Configure youtube streaming
  streamingscheduling:schedule-youtube   Schedule a streaming
```

## Configure youtube streaming

create an OAuth application in your Google settings. [Google API settings](https://console.developers.google.com/)
Go to "`APIs & Services`" => "`Credentials`" and click on "`+ CREATE CREDENTIALS`" -> "`OAuth client ID`".
Set the "`Application type`" to "`Web application`" and give a name to the application.

> Make sure you set one "Authorized redirect URI" to `<PROTOCOL>://<DOMAIN>/apps/streamingschedule/oauth-redirect`

Go to "`APIs & Services`" => "`Library`" and add th` following API:
"Google YouTube API".

Use "`Client ID`" and "`Client secret`" on command `streamingscheduling:config-youtube`

After run the command `streamingscheduling:config-youtube` you will see the URL to authorize access to your account on terminal. Access this URL on your browser to get the access token to your Google Account.

## Schedule a streaming

Use the follow commando to schedule a stream:

```bash
streamingscheduling:schedule-youtube
```

Example:

```
occ streamingscheduling:stream admin \
    --title="Title" \
    --description="Description" \
    --start-time=2021-12-28T10:30:00-03:00 \
    --thumbnail=/var/www/html/themes/example/core/img/logo.png
```