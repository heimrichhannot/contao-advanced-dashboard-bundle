- Use Symfony PHP Translation Format for Contao and Other Translations where possible when creating new files.
- Use Doctrine Schema Representation for Contao DCA SQL Column Definition.
- Do not define a custom targetColumn for Contao DCA virtual fields unless explicitly requested.
- Use one class per dca callback. Create the classes in src/EventListener/DataContainer/[Table] where table is the table name without tl_ prefix and CamelCalse, for example Member for tl_member. Name the classes after the callback name with Listener suffix, for example ConfigOnLoadListener for 'config.onload' or FieldsExampleOptionsListener for 'fields.example.options'
- Use AbstractBundle class for the bundle class, if possible

