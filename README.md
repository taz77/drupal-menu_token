# menu_token

After some trial and error I create a basic idea and module.

My thinking or idea is like that:

* There is global configuration where admin chose what kind of entities does it want on the special route page.

* When the menu item is edited there is Use tokens in title and in path. like in version for 7 and all the configuration
is listed for the entities that are selected in the global configuration.

> I found out that if I hook on `menu_token_form_menu_link_content_menu_link_content_form_alter`
then it will only hook on menu items that have a path and are not from views...
> Did not found an object way of doing it. Hook alter is the only way at least as I know it.

* Chose what admin want in a link form context, random and so on...

* When it is saved the reuteBuilder must be call to dispatch route event.

* There is a hook menu_token_menu_links_discovered_alter that runs and rebuilds the links
