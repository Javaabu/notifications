---
title: Dhivehi and RTL
sidebar_position: 5
---

# Strings

Every string the package shows is a JSON translation, in `lang/en.json` and `lang/dv.json`. Change one by adding the same key to your app's `lang/dv.json`:

```json
{
    "Mark all as read": "ހުރިހާ ނޯޓިފިކޭޝަނެއް ކިޔުނީއޭ ބަލާ"
}
```

The React bell is Onu's `NotificationCenter`, whose strings come from Onu's own `dv` locale (set `OnuProvider`'s locale). The React page gets its strings from the server (`strings`).

Your notifications' own text is yours: write it with `__()` and add the Dhivehi to your app's `lang/dv.json`.

# Right to left

- The page, the bell and the React components use logical utilities only (`ms-`, `pe-`, `start-`, `end-`), so they follow the page's `dir`. The bell's panel opens towards the start of the page. A test keeps physical utilities out.
- Titles and bodies have `dir="auto"`, so a Dhivehi notification reads right to left on an English page and the other way round.
- Mail in an RTL locale (`notifications.mail.rtl_locales`) is right to left: see [Branded mail](../basic-usage/branded-mail.md#right-to-left).
- The package has no free-text inputs; its filters are selects.

# Translations to review

These Dhivehi translations were written for the package and should be checked by a Dhivehi speaker:

| English | Dhivehi | Note |
| --- | --- | --- |
| Notifications | ނޯޓިފިކޭޝަންތައް | Loanword; ޚަބަރުތައް or އެންގުންތައް are alternatives |
| Mark all as read | ހުރިހާ ނޯޓިފިކޭޝަނެއް ކިޔުނީއޭ ބަލާ | |
| Mark as read / Mark as unread | ކިޔުނީއޭ ބަލާ / ނުކިޔާ ކަމަށް ބަލާ | |
| Marked as read / Marked as unread | ކިޔުނީއޭ ބެލިއްޖެ / ނުކިޔާ ކަމަށް ބެލިއްޖެ | |
| Read / Unread | ކިޔާފައި / ނުކިޔާ | Status labels |
| You're all caught up. | އާ އެއްވެސް ކަމެއް ނެތް. | Literally "nothing new" |
| Hello! | އައްސަލާމު ޢަލައިކުމް! | The mail greeting |
| Whoops! | މައްސަލައެއް ދިމާވެއްޖެ! | The greeting of error mail |
| Regards, | އިޚްތިރާމާއެކު، | |
| All rights reserved. | ހުރިހާ ޙައްޤެއް ރައްކާތެރިކޮށްފައިވޭ. | |
| If you're having trouble clicking the ":actionText" button, … | ":actionText" ފިތަށް ފިއްތުމުގައި ދަތިއެއް ވާނަމަ، … | The mail's fallback line |
| :count new notifications | :count އާ ނޯޓިފިކޭޝަން | Screen reader announcement |
| Filter | ފިލްޓަރ ކުރޭ | |
