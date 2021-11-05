Phersistence
============

# Run tests:

```sh
$ php cli.php associations
$ php cli.php constraints
$ php cli.php fields
```

# Optimizations for has-one

A has-one B

a: A
b1, b2: B

```php
$d->create_table('b');

$d->create_table('a');
$d->add_column('a', 'b_id', 'int', true);
$d->add_fk('a', 'b_id', 'fk_a_b', 'b', 'id');
```

> Note: there are two kinds of is_dirty, one is the flag maintained for each hasone attribute and _id field, and the second is evaluated on save/update considering the values of both hasone attribute and _id field. For instance, if the final value for one of them is NULL, buy in the middle there were different values assigned before the save happened, then on save/update the NULL values of either field should be evaluated alongside with the value of the is_dirty flag of each attribute, that is to know the final value that will be used in the dirty check.

## Without is_dirty per eahc has-one:

a->b_id | a->b        | case
--------|-------------|--------------
NULL    | NOT_LOADED  | `b` is not set
NULL    | NULL        | `b` is not set
NULL    | b1          | `b` was set after `a` was loaded but is not yet saved
n       | NOT_LOADED  | `b` is set but not loaded, lazy load from db
n       | NULL        | `b` is set but was set to null and `a` wasn't updated yet
n       | b1          | `b` is set and loaded, if `b->id` is different from `n`, `b` had a previous value and `a` wasn't updated yet (this case is only solved by an array of dirty attributes in `a`)


## With is_dirty per each has-one association:

A. `is_dirty_b` should be true when `b` is modified in these ways:

1. `b` = NULL, then `b` = b1
2. `b` = b1, then `b` = NULL
3. `b` = b1, then `b` = b2 / `b_id` != b2->id
4. `b` = NOT_LOADED, then `b` = b1 / `b_id` != b1->id

> The user shouldn't be able to set NOT_LOADED.

B. `is_dirty_b_id` should be true when `b_id` is modified in these ways:

1. `b_id` = n, then `b_id` = NULL
2. `b_id` = NULL, then `b_id` = n
3. `b_id` = n, then `b_id` = m / n != m


### Table for changes in a->b and how they affect the a->is_dirty_b:

> All cases maintain a->b_id unchanged.

a->b_id | a->b        | a->is_dirty_b | case
--------|-------------|---------------|------
NULL    | NOT_LOADED  | false         | not possible (if b_id = NULL, then b != NOT_LOADED)
NULL    | NOT_LOADED  | true          | not possible <sup>[1](#1)</sup>
NULL    | NULL        | false         | `b` is not set
NULL    | NULL        | true          | not possible <sup>[2](#2)</sup>
NULL    | b1          | false         | not possible <sup>[3](#3)</sup>
NULL    | b1          | true          | `b` = NULL, then `b` = b1 (A.1)
n       | NOT_LOADED  | false         | `b` is set but not loaded yet
n       | NOT_LOADED  | true          | not possible <sup>[1](#1)</sup>
n       | NULL        | false         | not possible <sup>[4](#4)</sup>
n       | NULL        | true          | `b` = b1, then `b` = NULL (A.2)
n       | b1          | false         | `b` is set and loaded and `n` = `b1->id`
n       | b1          | true          | `b` = b1, then `b` = b2 / `b_id` != b2->id, or, `b` = NOT_LOADED, then `b` = b1 / `b_id` != b1->id (A.3 or A.4) <sup>[5](#5)</sup>

<a href="#1">[1]</a>: is_dirty_b can't be true because the user can't set NOT_LOADED
<a href="#2">[2]</a>: is_dirty_b can't be true because `b_id` is still NULL, what could occur is `b_id` and `b` are NULL, then b is set to b1 without saving `a`, then `b` is set to NULL again, Phersistent should detect this case and resent the `is_dirty_b` to false by checking the value of `b_id`.
<a href="#3">[3]</a>: is_dirty_b can't be false because if `b_id` didn't change, `b` was set to `b1` without saving `a`, which means, `b` should be dirty.
<a href="#4">[4]</a>: is_dirty_b can't be false because `a` has a `b_id`, so `b` should be NOT_LOADED or `b1`, in this case the user did `b` = NULL, so `b` should be dirty.
<a href="#5">[5]</a>: the final state of this case can't differentiate if the previous value was NOT_LOADED or `b1`, either way the dirty flag is on because the newly assigned instance of `b` has a different id from `b_id`, `b1->id` could even be NULL.

### Table for changes in a->b_id and how they affect the a->is_dirty_b_id:

> All cases maintain a->b unchanged.

a->b_id | a->b        | a->is_dirty_b_id | case
--------|-------------|------------------|------
NULL    | NOT_LOADED  | false            | not possible <sup>[6](#6)</sup>
NULL    | NOT_LOADED  | true             | `b_id` = n, then `b_id` = NULL (B.1)
NULL    | NULL        | false            | `b` is not set
NULL    | NULL        | true             | not possible (at the save/update evaluation, could happen in the is_dirty_b_id) <sup>[7](#7)</sup>
NULL    | b1          | false            | not possible <sup>[8](#8)</sup>
NULL    | b1          | true             | `b_id` = n, then `b_id` = NULL (B.1)
n       | NOT_LOADED  | false            | `a` loaded from the database without any changes
n       | NOT_LOADED  | true             | `b_id` = n, then `b_id` = m / n != m (B.3)
n       | NULL        | false            | not possible <sup>[9](#9)</sup>
n       | NULL        | true             | `b_id` = NULL, then `b_id` = n (B.2)
n       | b1          | false            | `a` loaded from the database and `b` was accessed to read, so it was loaded
n       | b1          | true             | same as above, and includes: `b_id` = n, then `b_id` = m / n != m (B.3)

<a href="#6">[6]</a>: if this case happens is because `b_id` wasn't NULL and was set as NULL, so the `is_dirty_b_id` should be true.
<a href="#7">[7]</a>: `is_dirty_b_id` can't be true because `b` = NULL and `b_id` is NULL too (this should be checked before save because in the middle `b_id` could be set to something then to NULL again)
<a href="#8">[8]</a>: this case had a `b1` assigned to `b` but the `b_id` was set to NULL, so it can't have `is_dirty_b_id` in false.
<a href="#9">[9]</a>: if `b` = NULL, there is no hasone associated so on this case `b_id` was modified, so `is_dirty_b_id` can't be false.
