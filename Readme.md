# PHPUnit Firstruner

Hi, I'm Christophe and I got tired of having a display that was too reductive with PHPUnit.
So I build an extension for PHPUnit that give a result about unit test more beautifull, but also take some parameters for have a checking on the memory consuming and time elapsed.


# How Use

## Call reference

Simply ! You must call the reference to "Doctrine\Common\Annotations\UnitTestAnnotation" like this :
`use Doctrine\Common\Annotations\UnitTestAnnotation;`

## Parameters

### name
name is a required field, it give a name to your class or method

### description
description is an optional fiels, it give you a description in your test results

### item
item is a name of the tested POO object, like the class name

### element
element is a name of the tested POO function, property, ...

### memoryLimit
memoryLimit fixe a limit about consumption limit for a class or method (not implemented for a method)
The value is expressed in octets

### executionTimeLimit
executionTimeLimit fixe a limit for execute all assertion in a test or in class test
The value is expressed in seconds

## Parameter a test class

Just add UnitTestAnnotation to your class and herits of TestCase_Firstruner instead of TestCase, like this :

`/**`
`* @UnitTestAnnotation(`
`* name="Annotation",`
`* description="Test des annotations",`
`* item="Annotation",`
`* element="Class",`
`* memoryLimit=8000000,`
`* executionTimeLimit=1)`
`*/`
`class  AnnotationTest  extends  TestCase_Firstruner`
`{`
`...[Your tests codes]...`
`}`

## Parameter a test

Just add UnitTestAnnotation to your test function, like this :

`/**`
`* @UnitTestAnnotation(`
`* name="This is a test",`
`* description="Annotation about method",`
`* item="Annotation",`
`* element="Function",`
`* executionTimeLimit=2)`
`*/`
`class  AnnotationTest  extends  TestCase_Firstruner`
`{`
`...[Your tests codes]...`
`}`

## How run test
Like PHPUnit but with some change :
`php "phpunit_firstruner/phpunit_firstruner" [..Your common parameters, files, directories..]`

# Result
![enter image description here](https://gitlab.com/firstruner/unittest_firstruner/-/raw/master/Preview.jpg)