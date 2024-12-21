<?php

abstract class AbstractForm extends AbstractFormCore {


    public function fillWith(array $params = [])
    {
        $newFields = $this->formatter->getFormat();

        foreach ($newFields as $field) {
            if (array_key_exists($field->getName(), $this->formFields)) {
                // keep current value if set
                $field->setValue($this->formFields[$field->getName()]->getValue());
            }

            if (array_key_exists($field->getName(), $params)) {
                // overwrite it if necessary
                $field->setValue($params[$field->getName()]);
            } elseif ($field->getType() === 'checkbox') {
                // checkboxes that are not submitted
                // are interpreted as booleans switched off
                if (empty($field->getValue())) {
                    $field->setValue(false);
                }
            }

        }

        $this->formFields = $newFields;

        if ($this instanceof CustomerAddressForm && isset($params['id_address'])) {
            Hook::exec('afterFillingEditAddressForm', ['form' => $this]);
        }

        return $this;
    }
}