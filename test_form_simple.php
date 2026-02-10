<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Entity\User;
use App\Form\ParentRegistrationType;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Validator\Validation;

echo "=== Testing Form Submission (Simplified) ===\n\n";

// Create validator
$validator = Validation::createValidatorBuilder()
    ->enableAnnotationMapping()
    ->getValidator();

// Create form factory
$formFactory = Forms::createFormFactoryBuilder()
    ->addExtension(new HttpFoundationExtension())
    ->addExtension(new ValidatorExtension($validator))
    ->getFormFactory();

// Create a mock POST request data
$formData = [
    'lastName' => 'talbi',
    'firstName' => 'bessem',
    'email' => 'bessemtalbi0@gmail.com',
    'telephone' => '12345678',
    'adresse' => 'rue el merikh , el mourouj',
    'password' => [
        'first' => 'password123',
        'second' => 'password123'
    ]
];

// Create form
$user = new User();
$form = $formFactory->create(ParentRegistrationType::class, $user, [
    'csrf_protection' => false,
]);

echo "Form created successfully\n";

// Submit the form
$form->submit($formData);

echo "Form submitted: " . ($form->isSubmitted() ? 'YES' : 'NO') . "\n";
echo "Form valid: " . ($form->isValid() ? 'YES' : 'NO') . "\n\n";

if (!$form->isValid()) {
    echo "=== VALIDATION ERRORS ===\n";
    
    // Get all errors
    $errors = $form->getErrors(true);
    if (count($errors) === 0) {
        echo "No errors found with getErrors()\n";
    } else {
        foreach ($errors as $error) {
            echo "- " . $error->getMessage() . "\n";
            echo "  Origin: " . $error->getOrigin()->getName() . "\n";
        }
    }
    
    // Check each field
    echo "\n=== FIELD-BY-FIELD ANALYSIS ===\n";
    foreach ($form->all() as $child) {
        $childName = $child->getName();
        $childData = $child->getData();
        $childErrors = $child->getErrors();
        
        if (count($childErrors) > 0) {
            echo "\nField: $childName\n";
            echo "Data: " . (is_string($childData) ? $childData : json_encode($childData)) . "\n";
            echo "Errors:\n";
            foreach ($childErrors as $error) {
                echo "  - " . $error->getMessage() . "\n";
            }
        } else {
            echo "\nField: $childName - OK\n";
            echo "Data: " . (is_string($childData) ? $childData : json_encode($childData)) . "\n";
        }
    }
    
    // Special check for password
    if ($form->has('password')) {
        $passwordField = $form->get('password');
        echo "\n=== PASSWORD FIELD ===\n";
        echo "Type: " . get_class($passwordField->getConfig()->getType()->getInnerType()) . "\n";
        
        $options = $passwordField->getConfig()->getOptions();
        echo "Mapped: " . ($options['mapped'] ?? 'NOT SET') . "\n";
        
        if ($passwordField->has('first')) {
            $first = $passwordField->get('first');
            echo "First field data: " . ($first->getData() ?: 'NULL') . "\n";
            $firstErrors = $first->getErrors();
            if (count($firstErrors) > 0) {
                echo "First field errors:\n";
                foreach ($firstErrors as $error) {
                    echo "  - " . $error->getMessage() . "\n";
                }
            }
        }
    }
} else {
    echo "=== FORM IS VALID ===\n";
    echo "User email: " . $user->getEmail() . "\n";
    echo "User password set: " . ($user->getPassword() ? 'YES' : 'NO') . "\n";
}

// Show what data was submitted
echo "\n=== FORM DATA SUBMITTED ===\n";
print_r($formData);

// Also check the User entity
echo "\n=== USER ENTITY STATE ===\n";
echo "LastName: " . $user->getLastName() . "\n";
echo "FirstName: " . $user->getFirstName() . "\n";
echo "Email: " . $user->getEmail() . "\n";
echo "Password: " . ($user->getPassword() ? 'SET' : 'NOT SET') . "\n";

// Check if password field exists in the form
echo "\n=== FORM STRUCTURE ===\n";
foreach ($form->all() as $child) {
    $options = $child->getConfig()->getOptions();
    echo $child->getName() . " (mapped: " . ($options['mapped'] ?? 'false') . ")\n";
}
