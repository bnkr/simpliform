<?php
namespace SimpliformTest;

use PHPUnit_Framework_TestCase as TestCase;
use Simpliform\Form;
use Simpliform\Field;
use SimpliformTest\Fixture;

class FormTest extends TestCase
{
    public function testValidationCanBeDisabledDependingOnInputData()
    {
        $form = new Form();
        $form->addValidation('field', function($value, $context) {
            return $value == 'good-value';
        });
        $form->addPreparation(function($form) {
            if ($form->getData()['validate']) {
                return;
            }
            $form->disable('field');
        });

        $form->setData(array(
            'validate' => '1',
            'field' => "bad-value",
        ));
        $this->assertEquals(false, $form->isValid());

        $form->setData(array(
            'validate' => '0',
            'field' => "bad-value",
        ));
        $this->assertEquals(true, $form->isValid());
    }

    public function testSimpleValidation()
    {
        $form = new Form();
        $form->addValidation('field', function($value, $form) {
            $data = $form->getRawData();
            return $data['field'] == "1";
        });
        $form->setData(array('field' => 1));
        $this->assertEquals(true, $form->isValid());
        $form->setData(array('field' => 0));
        $this->assertEquals(false, $form->isValid());
    }

    public function testDependentValidationTriggersOtherValidation()
    {
        $form = new Form();
        $form->addProcessing('other', function($context) {
            $context->fail("invalid");
        });
        $form->addValidation('whatever', function($value, $context) {
            $other = $context->get('other');
            // never reached
            return true;
        });
        $form->setData(array('other' => 'x', 'whatever' => "10"));
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals(false, $form->getErrors()->get('whatever')->isValid());
        $this->assertEquals(false, $form->getErrors()->get("other")->isValid());
    }

    public function testDependentValidationUsesOtherProcessing()
    {
        $form = new Form();
        $form->addProcessing('other', function($value, $context) {
            return $value * 10;
        });
        $form->addValidation('whatever', function($value, $context) {
            return $context->get('other') == 100;
        });

        $form->setData(array('other' => '10', 'whatever' => "100"));
        $this->assertEquals(true, $form->isValid());
        $this->assertEquals(false, $form->getErrors()->isValid('whatever'));
        $this->assertEquals(false, $form->getErrors()->isValid('other'));
    }

    public function testErrorFromFieldIsValidationFailure()
    {
        $this->fail("ni");
    }

    public function testRequirednessIsConfigureable()
    {
        // should be easy to enable/disable a field from being empty or not
        $this->fail("ni");
    }

    public function testFieldsHaveValidationFunctions()
    {
        // fields should be able to supply validation functions for the form to
        // run
        $this->fail("ni");
    }

    public function testInlineFieldGroupIsMergedIntoForm()
    {
        $data = array(
            'field' => 1,
            'a' => "b",
            'c' => "d",
        );
        $this->fail("ni");
    }

    public function testOutlineFieldGroupIsArray()
    {
        $data = array(
            'field' => 1,
            'group' => array(
                'a' => "b",
                'c' => "d",
            ),
        );
        $this->fail("ni");
    }

    public function testFieldGroupsCanBeRepeated()
    {
        $data = array(
            'mainfield' => 1,
            'subform' => array(
                array(
                    'field' => 1,
                ),
                array(
                    'field' => 1,
                ),
            ),
        );
        $this->fail("ni");
    }

    public function testDataIsFilteredByFields()
    {
        $form = new Form();
        $form->addField('whatever', new Field\BooleanField());

        $form->setData(array('whatever' => '1',));

        $processed = $form->getOutput();
        $this->assertSame(true, $processed['whatever']);
    }

    public function testFieldsLoadedByName()
    {
        $form = new Form();
        $form->addField('whatever', 'boolean', array('required' => true));

        $this->assertEquals(true, $form->getField()->isRequired());

        $form->setData(array('whatever' => '1',));

        $processed = $form->getOutput();
        $this->assertSame(true, $processed['whatever']);
    }

    public function testStatefulFieldsArePossible()
    {
        $stateful = new Fixture\StatefulField();
        $stateful->setProcess(function($data) {
            if ($data['data'] == '1') {
                unset($data['data']);
            } else {
                $data['other_data'] = 2;
            }
        });

        $form = new Form();
        $form->addField('data', $stateful);
        $form->setData(array(
            'data' => '1',
        ));

        // pretend this is the file upload we will actually use
        $data = $form->getOutput();
        $this->assertEquals('1', $data['data']);

        // ... and this is the data we use to present the next rendered form.
        $data = $form->getRenderData();
        $this->assertEquals(false, isset($data['data']));
        $this->assertEquals('2', $data['other_data']);
    }

    public function testValidFormCanBeManuallyInavlidated()
    {
        $fornm = new Form();
        $form->add('name', 'text');
        $form->setData(array("name" => "blah"));
        $this->assertEquals(true, $form->isValid());

        $form->addMessage('fieldset.other', "It's invalid.");

        $this->assertEquals(false, $form->isValid());
    }

    public function testDefaultRenderingShowsHtml()
    {
        $this->fail("ni");
    }

    public function testRenderingParametersAreHintedByField()
    {
        // add something like a css class and test the renderer sees it
        $this->fail("ni");
    }
}
