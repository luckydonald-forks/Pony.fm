<?php

/**
 * Pony.fm - A community for pony fan music.
 * Copyright (C) 2016 Peter Deltchev
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Poniverse\Ponyfm\Console\Commands;

use Carbon\Carbon;
use DB;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Poniverse\Ponyfm\Commands\MergeAccountsCommand;
use Poniverse\Ponyfm\Models\Album;
use Poniverse\Ponyfm\Models\Comment;
use Poniverse\Ponyfm\Models\Favourite;
use Poniverse\Ponyfm\Models\Follower;
use Poniverse\Ponyfm\Models\Image;
use Poniverse\Ponyfm\Models\PinnedPlaylist;
use Poniverse\Ponyfm\Models\Playlist;
use Poniverse\Ponyfm\Models\ResourceLogItem;
use Poniverse\Ponyfm\Models\ResourceUser;
use Poniverse\Ponyfm\Models\Track;
use Poniverse\Ponyfm\Models\User;

class MergeAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accounts:merge
                            {sourceAccountId : ID of the source account (the one being disabled and having content transferred out of it)}
                            {destinationAccountId : ID of the destination account (the one gaining content)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merges two accounts';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $sourceAccountId = $this->argument('sourceAccountId');
        $destinationAccountId = $this->argument('destinationAccountId');

        $sourceAccount = User::find($sourceAccountId);
        $destinationAccount = User::find($destinationAccountId);

        $this->info("Merging {$sourceAccount->display_name} ({$sourceAccountId}) into {$destinationAccount->display_name} ({$destinationAccountId})...");

        $command = new MergeAccountsCommand($sourceAccount, $destinationAccount);
        $command->execute();
    }
}