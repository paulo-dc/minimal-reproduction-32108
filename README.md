# minimal-reproduction-32108

## Current behavior

Hello, I'm trying to configure renovate to keep our project up to date. We use wordpress, and want to keep also up to date wordpress plugins. We installed the plugins in the folder ``wp-content/plugins`` with composer.

But when we run renovate to do a deepcheck, it install the plugins in the folder ``vendor`` 😓 .

## Expected behavior

We wanted to update the wordpress plugins in the folder ``wp-content/plugins``

## Link to the Renovate issue or Discussion

[https://github.com/renovatebot/renovate/discussions/32108](https://github.com/renovatebot/renovate/discussions/32108)
