# Script Tests to DebbieTestCase Transformation

## Summary

Transformed script_tests into proper DebbieTestCase tests organized by functionality.

## New Test Files Created

### Fields Tests (tests/fields/)
1. **TestZeroNull.php** - Tests that zero values (0, 0.0) persist correctly and aren't converted to NULL
   - Source: script_tests/test_zero_null.php

2. **TestSerializedObjectField.php** - Tests SOBJECT field type functionality
   - Source: script_tests/test_serialized_object.php
   - Tests: null sobject save/update, create with sobject, set_sobject, persistence, setProperties

### Query Tests (tests/query/)
1. **TestCountBy.php** - Tests countBy returns int type
   - Source: script_tests/test_count_by.php

2. **TestFindBy.php** - Tests complex query conditions
   - Source: script_tests/test_find_by.php
   - Tests: simple, AND, OR, AND OR, NOT AND, NOT simple queries

### Model Tests (tests/model/)
1. **TestDelete.php** - Tests delete functionality and deleted flag
   - Source: script_tests/test_delete.php

2. **TestDirty.php** - Tests is_dirty flag behavior
   - Source: script_tests/test_dirty.php
   - Tests: is_dirty on create, with associations, on load, after setProperties

### Association Tests (tests/associations/)
1. **TestHasManyLazyLoad.php** - Tests has many lazy loading and collection operations
   - Source: script_tests/test_hasmany_lazy_load.php
   - Tests: save, load, remove_from, add_to

## New Model Classes Created

### tests/model/
1. **NumberTest.php** - Model with INT, LONG, FLOAT, DOUBLE fields
2. **TestSObject.php** - Model with SOBJECT field
3. **A.php** - Model with has many PhSet relationship to B
4. **B.php** - Model with note field for has many tests

## Schema Updates (src/db/schema.php)

Added table definitions for:
- number_test
- phone_number
- test_sobject
- a (for has many tests)
- b (for has many tests)

Updated existing tables:
- employer: added 'name' field, made fields nullable
- member: added 'name' field, made fields nullable

## Script Tests Not Transformed

The following script tests were not transformed as they are more complex integration tests or examples:
- test_amplify.php - Complex example with nested associations
- test_note_hasmany_note.php - Self-referential has many (note has many note)
- test_ph11.php - Class definition iteration test
- test_ph12.php - Large integration test with STI, has many, validations
- test_validations.php - Constraint validation tests (mostly commented out)
- test_serialized_array.php - Already covered by existing TestSerializedArrayField.php

## Running the Tests

Run all new tests:
```sh
php cli.php fields
php cli.php query
php cli.php model
php cli.php associations
```

Run specific test cases:
```sh
php cli.php fields TestZeroNull
php cli.php query TestCountBy
php cli.php model TestDirty
php cli.php associations TestHasManyLazyLoad
```
