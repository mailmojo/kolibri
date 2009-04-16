<?php
// remember to set your own paths here..
define('APP_PATH', '/home/stian/projects/kolibri-testcase/examples/wishlist');
define('MODELS_PATH', APP_PATH . '/models');
define('ROOT', '/home/stian/projects/kolibri-testcase/src');
require(ROOT . '/specs/KolibriTestCase.php');

/**
 * Specification for the Article Model. It contains before and after methods from PHPSpec.
 * TODO: Alot of the spec methods are too large for their purpose. something needs to be
 * done...
 */
class DescribeItemModel extends KolibriTestCase {
    private $itemName;
    
    /**
     * This method acts as beforeAll() method from PHPSpec
     */
    public function setup () {
        // This method doesnt have to be here if it doesnt contain anything.
        // echo "this is invoked before every spec method\n";

    }
    
    /**
     * This method acts as before() method from PHPSpec
     */
    public function infront () {
        // This method doesnt have to be here if it doesnt contain anything.
        // echo "this is infront of every spec method\n";
        
        $item = $this->fixtures['ValidItem'];
        $item->save();

        $this->itemName = $item->name;
    }
    
    /**
     * This spec will try to load a saved article object
     */
    public function itShouldBeAbleToLoadAnArticle () {
        $item = Models::init('Item');
        $item->objects->load($this->itemName);
        
        $this->spec($item->name)->should()->beEqualTo($this->itemName);
    }
    
    /**
     * This spec will try to save an article object
     */
    public function itShouldHaveAnItemNameWhenSaved () {
        $item = $this->fixtures['ValidItem'];
        $this->spec($item)->should()->beValid();
        $article->save();
        
        $this->spec($item->name)->shouldNot()->beNull();
    }
    
    /**
     * This spec will try to save an invalid article object
     */
    public function itShouldNotBeAbleToSaveAnInvalidArticle () {
        $item = $this->fixtures['InvalidItem'];
        $this->spec($item)->shouldNot()->beValid();
        
        /*try {
            $item->save();

            // if it succeeds to come all this way, the test will fail
            // and it will output the model validation errors.
            $this->fail("Not a valid item");
        }
        catch(SQLException $e) {

        }*/

        $this->spec($item->name)->should()->beNull();
    }
    
    /**
     * This spec will try to delete a saved article object
     */
    public function itShouldBeAbleToDeleteAnItem () {
        $item = Models::init('Item');
        $item->objects->load($this->itemName);
        $item->delete();
        
        $item = Models::init('Item');
        $item->objects->load($this->itemName);
        $this->spec($item->name)->should()->beNull();
    }
    
    /**
     * This method acts as after() method from PHPSpec
     */
    public function tearDown () {
        // This method doesnt have to be here if it doesnt contain anything.
    }
    
    /**
     * This method acts as afterAll() method from PHPSpec
     */
    public function tearDownLast () {
        // This method doesnt have to be here if it doesnt contain anything.
    }
    

}
?>
