<?php

/*
 *       _       _        ___ _____ _  ___
 *   __ _| |_   _(_)_ __  / _ \___ // |/ _ \
 * / _` | \ \ / / | '_ \| | | ||_ \| | (_) |
 * | (_| | |\ V /| | | | | |_| |__) | |\__, |
 *  \__,_|_| \_/ |_|_| |_|\___/____/|_|  /_/
 *
 * Copyright (C) 2020 - 2021 alvin0319
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace alvin0319\OffHand;

use pocketmine\inventory\BaseInventory;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\item\Item;
use pocketmine\entity\Human;
use pocketmine\Player;
use pocketmine\utils\MainLogger;

class OffhandInventory extends BaseInventory
{
    /** @var Human */
    protected $holder;
    
    public function __construct(Human $holder)
    {
        $this->holder = $holder;
        parent::__construct();
    }
    
    public function getName(): string
    {
        return "Offhand";
    }
    
    public function getDefaultSize(): int
    {
        return 1;
    }
    
    public function setSize(int $size)
    {
        throw new \BadMethodCallException("Offhand can only carry one item at a time");
    }
    
    public function setItem(int $index, Item $item, bool $send = true): bool
    {
        parent::setItem($index, $item, $send);
        $this->sendItem();
        $this->sendContents($this->getHolder());
        
        $this->getHolder()->namedtag->setTag($item->nbtSerialize(-1, "OffHand"));
        return true;
    }
    
    public function sendItem(array $players = null): void
    {
        $players = $players ?? $this->holder->getViewers();
        $pk = new MobEquipmentPacket;
        $pk->windowId = ContainerIds::OFFHAND;
        $pk->item = $this->getItem(0);
        $pk->inventorySlot = $pk->hotbarSlot = 0;
        $pk->entityRuntimeId = $this->getHolder()->getId();
        foreach ($players as $player) {
            try {
                $player->batchDataPacket($pk);
            } catch (\Error $err) {
                MainLogger::getLogger()->logException($err);
            }
        }
    }
    
    /**
     * This override is here for documentation and code completion purposes only.
     *
     * @return Player|Human
     */
    public function getHolder()
    {
        return $this->holder;
    }
    
    public function setHolder($holder)
    {
        $this->holder = $holder;
        return $this;
    }
}
