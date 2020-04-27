<?php

/*
 *       _       _        ___ _____ _  ___
 *   __ _| |_   _(_)_ __  / _ \___ // |/ _ \
 * / _` | \ \ / / | '_ \| | | ||_ \| | (_) |
 * | (_| | |\ V /| | | | | |_| |__) | |\__, |
 *  \__,_|_| \_/ |_|_| |_|\___/____/|_|  /_/
 *
 * Copyright (C) 2020 alvin0319
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
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\Player;

class OffHandInventory extends BaseInventory{

	/** @var OffHandPlayer */
	protected $holder;

	public function __construct(Player $holder){
		parent::__construct([], 1);
		$this->holder = $holder;
	}

	public function getPlayer() : Player{
		return $this->holder;
	}

	public function setSize(int $size) : void{
		throw new \BadMethodCallException("Cannot call setSize on OffHandInventory");
	}

	public function getName() : string{
		return "OffHandInventory";
	}

	public function getDefaultSize() : int{
		return 1;
	}

	public function setItemInOffHand(Item $item) : void{
		$this->setItem(0, $item);

		$pk = new InventorySlotPacket();
		$pk->windowId = ContainerIds::OFFHAND;
		$pk->inventorySlot = 0;
		$pk->item = $this->getItemInOffHand();
		$this->holder->getServer()->broadcastPacket($this->holder->getViewers(), $pk);
		$this->holder->sendDataPacket($pk);
	}

	public function getItemInOffHand() : Item{
		return $this->getItem(0);
	}
}