<div class="main">
    <div class="shell">
        <h1><?=$this->lng['search']['title'];?></h1>

        <p><?=$this->lng['search']['resultats']?> : <b>"<?=$this->search?>"</b></p>

        <ul style="padding-left:50px;list-style-type:inherit;">
        <?php
        if($this->result == true)
        {
            foreach($this->result as $result)
            {
            	?><li><a href="<?=$result['slug']?>"><?=$result['title']?></a></li><?
            }
        }
        else
        {
            echo $this->lng['search']['pas-de-resultats'];
        }
        ?>
        </ul>

    </div><!-- /.shell -->
</div><!-- /.main -->
