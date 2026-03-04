<?php

namespace tests\associations;

use CaboLabs\Debbie\DebbieTestCase;

class TestHasManyLazyLoad extends DebbieTestCase {

  public function test_dummy()
  {

  }

  public function test_has_many_save()
  {
    global $A, $B;

    $b1 = $B->create(['note' => 'Hi, I called them but didnt got an answer, trying tomorrow']);
    $b2 = $B->create(['note' => 'I just called and was able to reach someone']);
    $b3 = $B->create(['note' => 'I just called and was able to reach someone']); // duplicate

    $a1 = $A->create(['bs' => [$b1, $b2, $b3]]);

    $a1->save();

    $this->assert($a1->size_bs() === 2, 'Duplicate not saved');
  }

  public function test_has_many_load()
  {
    global $A, $B;

    $b1 = $B->create(['note' => 'Note 1']);
    $b2 = $B->create(['note' => 'Note 2']);

    $a1 = $A->create(['bs' => [$b1, $b2]]);
    $a1->save();

    $a2 = $A->get($a1->get_id());

    $this->assert($a2->size_bs() === 2, 'Has many loaded correctly');
  }

  public function test_has_many_remove_from()
  {
    global $A, $B;

    $b1 = $B->create(['note' => 'Note 1']);
    $b2 = $B->create(['note' => 'Note 2']);

    $a1 = $A->create(['bs' => [$b1, $b2]]);
    $a1->save();

    $a3 = $A->get($a1->get_id());

    $a3->remove_from_bs_and_delete($b1);

    $this->assert($a3->size_bs() === 1, 'Item removed from memory');

    $a3->save();

    $a4 = $A->get($a1->get_id());

    $this->assert($a4->size_bs() === 1, 'Item removed from DB');
  }

  public function test_has_many_add_to()
  {
    global $A, $B;

    $b1 = $B->create(['note' => 'Note 1']);

    $a1 = $A->create(['bs' => [$b1]]);
    $a1->save();

    $a4 = $A->get($a1->get_id());

    $a4->add_to_bs($B->create(['note' => 'new note']));

    $this->assert($a4->size_bs() === 2, 'Item added to memory');

    $a4->save();

    $a5 = $A->get($a1->get_id());

    $this->assert($a5->size_bs() === 2, 'Item added to DB');
  }
}

?>
