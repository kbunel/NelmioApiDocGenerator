* @SWG\Response(
*     response=<?= $httpResponseCode ?>,
*     description="<?= $description ?>.",
<?php if ($model): ?>
*     @SWG\Schema(
*         type="array",
*         @SWG\Items(ref=@Model(type=<?= $model ?><?= ($groups) ? ', groups={' . $groups . '}' : '' ?>))
*     )
<?php endif ?>
* )
* @SWG\Tag(name="<?= $tag ?>")