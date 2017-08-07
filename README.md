## ilch2-twitterauth

***Latest version: [1.0.0](https://github.com/dastiii/ilch2-twitterauth/archive/1.0.0.zip) (BETA)***

#### Installation
Alle Dateien und Ordner nach `application/modules/twitterauth` kopieren und anschließend unter `Module > Übersicht > Nicht installierte` installieren.

#### Usage
Nach der Installation musst du dir zunächst auf [https://apps.twitter.com/](https://apps.twitter.com/) eine App erstellen und die Felder unter `Module > Anmelden mit Twitter > API Keys` mit deinen Daten ausfüllen.

Danach muss das Modul zunächst für Twitter aktiviert werden, navigiere hierzu nach `Benutzer > Authentifizierungsanbieter` und wähle `Anmelden mit Twitter` aus.

Anschließend können bereits angemeldete Benutzer im `User Panel` unter `Social Media` ihr Benutzerkonto mit ihrem Twitterkonto verknüpfen.

Außerdem ist nun beim Login-Formular ein Twitterlogo zur Anmeldung per Twitter sichtbar. Hat ein Benutzer seine Konten bereits verknüpft, kann er sich mit einem Klick auf das Twitterlogo direkt anmelden. Neue Benutzer können auf diese Weise direkt ein neues Benutzerkonto erstellen und dabei direkt mit ihrem Twitterkonto verknüpfen.

#### Passwort
Bei der Registration per Twitter wird für das neue Benutzerkonto automatisch ein sicheres Passwort generiert, dieses Passwort wird *nicht* mitgeteilt, daher muss sich der Benutzer entweder immer mit seinem Twitterkonto anmelden oder ein neues Passwort im `User Panel` setzen. Notfalls kann dies über die `Passwort vergessen?`-Funktion geschehen.
