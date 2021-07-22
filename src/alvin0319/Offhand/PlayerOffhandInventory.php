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

namespace alvin0319\Offhand;

use pocketmine\inventory\BaseInventory;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\Player;
use function count;

class PlayerOffhandInventory extends BaseInventory{

	public const SLOT_OFFHAND = 0;

	/** @var Player */
	protected Player $holder;

	public function __construct(Player $holder){
		parent::__construct();
		$this->holder = $holder;
	}

	public function getName() : string{
		return "OffhandInventory";
	}

	public function getDefaultSize() : int{
		return 1;
	}

	public function getItemInOffhand() : Item{
		return $this->getItem(0);
	}

	public function setItemInOffhand(Item $item) : void{
		$this->setItem(self::SLOT_OFFHAND, $item);
	}

	public function onSlotChange(int $index, Item $before, bool $send) : void{
		parent::onSlotChange($index, $before, $send);
		$this->broadcastMobEquipment();
	}

	public function broadcastMobEquipment(array $players = []) : int{
		if(count($players) === 0){
			$players = $this->holder->getViewers() + [$this->holder]; // viewers dont have himself
		}
		$pk = new MobEquipmentPacket();
		$pk->item = ItemStackWrapper::legacy($this->getItemInOffhand());
		$pk->inventorySlot = $pk->hotbarSlot = 0;
		$pk->windowId = ContainerIds::OFFHAND;
		$pk->entityRuntimeId = $this->holder->getId();

		$this->holder->getServer()->broadcastPacket($players, $pk);
		return count($players);
	}
}
