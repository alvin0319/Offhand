# OffHand
A PocketMine-MP Plugin that implements OffHand (and OffHandInventory)

## Todo

* [x] Off hand Inventory
* [x] Transaction handle
* [x] Off hand item store
* [ ] More...

## License

See [License](https://github.com/alvin0319/OffHand/tree/master/LICENSE)

## API Document

```php
/** @var \alvin0319\OffHand\OffHandPlayer $player */
$offHand = $player->getOffHandInventory(); // Get Offhand inventory
$offHandItem = $offHand->getItemInOffHand(); // Get Offhand item

/** @var \pocketmine\item\Item $item */
$offHand->setItemInOffHand($item); // Set offhand item
```

## Image

![](https://raw.githubusercontent.com/alvin0319/OffHand/master/image.png)