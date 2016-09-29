Simpliform
==========

Simpliform is a form processing library for PHP with very configurable
processing behaviour without having many APIs to learn::

  $form = new Simpliform();
  $form->addFilter('field', function($context) {
      return $context->getValue() * 10;
  });

  $form->setInput(array(
      'field' => "10",
  ));

  $processed = $form->getOutput();
  var_dump($processed);
  // array('field' => 20);

Currently a work in progress with many unfinished features.  Unless you want to
steal bits of it or make patches it's probably best to avoid for now.
