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

use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\MobEquipmentPacket;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\Player;

class OffHandPlayer extends Player{

	/** @var OffHandInventory */
	protected $offhandInventory;

	protected function initEntity() : void{
		$this->offhandInventory = new OffHandInventory($this);
		if($this->namedtag->hasTag("offHand", CompoundTag::class)){
			$this->offhandInventory->setItemInOffHand(Item::nbtDeserialize($this->namedtag->getCompoundTag("offHand")));
		}
		parent::initEntity();
	}

	public function addDefaultWindows() : void{
		parent::addDefaultWindows();
		$this->addWindow($this->offhandInventory, ContainerIds::OFFHAND, true);
	}

	public function getOffHandInventory() : OffHandInventory{
		return $this->offhandInventory;
	}

	public function saveNBT() : void{
		parent::saveNBT();
		$this->namedtag->setTag($this->offhandInventory->getItemInOffHand()->nbtSerialize(-1, "offHand"));
	}

	public function handleMobEquipment(MobEquipmentPacket $packet) : bool{
		if($packet->windowId === ContainerIds::OFFHAND){
			$item = $this->offhandInventory->getItem($packet->hotbarSlot);

			if(!$item->equals($packet->item)){
				$this->server->getLogger()->debug("Tried to equip " . $packet->item . " but have " . $item . " in target slot");
				$this->offhandInventory->sendContents($this);
				return false;
			}
			$this->offhandInventory->setItemInOffHand($packet->item);
			$this->namedtag->setTag($packet->item->nbtSerialize(-1, "offHand"));
			return true;
		}
		return parent::handleMobEquipment($packet);
	}
}