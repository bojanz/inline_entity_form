Experimental code that may or may not become Inline Entity Form 8.x-2.x.

The old IEF (8.x-1.x) used a form element to embed an entity form.
This has proven to be a bad idea: https://www.drupal.org/project/commerce/issues/3003121.
As a result, Commerce introduced its own InlineForm API, now moved to this module.

API
---
Inline forms are plugins. Automatically validated and submitted.
They are not entity-specific, and can be used for many use cases.

The "content_entity" inline form manages content entitites.
If the content entity is translatable, and the inline form is used as a part
of another content entity form, the entity langcodes will be kept in sync.
Translating the parent entity will also translate the entity managed by the
inline form.

Usage example:
```php
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Load or create a $node.
    $inline_form = $this->inlineFormManager->createInstance('content_entity', [], $node);

    $form['node'] = [
      '#parents' => array_merge($form['#parents'], ['node']),
      '#inline_form' => $inline_form,
    ];
    $form['node'] = $inline_form->buildInlineForm($form['node'], $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // The same logic would also work in validateForm().
    /** @var \Drupal\inline_entity_form\Plugin\InlineForm\EntityInlineFormInterface $inline_form */
    $inline_form = $form['node']['#inline_form'];
    $node = $inline_form->getEntity();
  }
```

Widgets
-------
Different widgets for different use cases. The more the merrier.

- Embedded

Shows the inline entity forms embedded on the parent form.
Equivalent to the 8.x-1.x  "Simple" widget.
