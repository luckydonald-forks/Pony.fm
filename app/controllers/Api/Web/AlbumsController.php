<?php

	namespace Api\Web;

	use Commands\CreateAlbumCommand;
	use Commands\DeleteAlbumCommand;
	use Commands\EditAlbumCommand;
	use Entities\Album;
	use Entities\Comment;
	use Entities\Image;
	use Entities\ResourceLogItem;
	use Entities\Track;
	use Illuminate\Support\Facades\Auth;
	use Illuminate\Support\Facades\Input;
	use Illuminate\Support\Facades\Response;

	class AlbumsController extends \ApiControllerBase {
		public function postCreate() {
			return $this->execute(new CreateAlbumCommand(Input::all()));
		}

		public function postEdit($id) {
			return $this->execute(new EditAlbumCommand($id, Input::all()));
		}

		public function postDelete($id) {
			return $this->execute(new DeleteAlbumCommand($id));
		}

		public function getShow($id) {
			$album = Album::with([
				'tracks' => function($query) { $query->userDetails(); },
				'tracks.cover',
				'tracks.genre',
				'tracks.user',
				'user',
				'comments',
				'comments.user'])
				->userDetails()
				->find($id);

			if (!$album)
				App::abort(404);

			if (Input::get('log')) {
				ResourceLogItem::logItem('album', $id, ResourceLogItem::VIEW);
				$album->view_count++;
			}

			return Response::json([
				'album' => Album::mapPublicAlbumShow($album)
			], 200);
		}

		public function getIndex() {
			$page = 1;
			if (Input::has('page'))
				$page = Input::get('page');

			$query = Album::summary()
				->with('user', 'user.avatar', 'cover')
				->userDetails()
				->orderBy('created_at', 'desc')
				->where('track_count', '>', 0);

			$count = $query->count();
			$perPage = 40;

			$query->skip(($page - 1) * $perPage)->take($perPage);
			$albums = [];

			foreach ($query->get() as $album) {
				$albums[] = Album::mapPublicAlbumSummary($album);
			}

			return Response::json(["albums" => $albums, "current_page" => $page, "total_pages" => ceil($count / $perPage)], 200);
		}

		public function getOwned() {
			$query = Album::summary()->where('user_id', \Auth::user()->id)->orderBy('created_at', 'desc')->get();
			$albums = [];
			foreach ($query as $album) {
				$albums[] = [
					'id' => $album->id,
					'title' => $album->title,
					'slug' => $album->slug,
					'created_at' => $album->created_at,
					'covers' => [
						'small' => $album->getCoverUrl(Image::SMALL),
						'normal' => $album->getCoverUrl(Image::NORMAL)
					]
				];
			}
			return Response::json($albums, 200);
		}

		public function getEdit($id) {
			$album = Album::with('tracks')->find($id);
			if (!$album)
				return $this->notFound('Album ' . $id . ' not found!');

			if ($album->user_id != Auth::user()->id)
				return $this->notAuthorized();

			$tracks = [];
			foreach ($album->tracks as $track) {
				$tracks[] = [
					'id' => $track->id,
					'title' => $track->title
				];
			}

			return Response::json([
				'id' => $album->id,
				'title' => $album->title,
				'user_id' => $album->user_id,
				'slug' => $album->slug,
				'created_at' => $album->created_at,
				'published_at' => $album->published_at,
				'description' => $album->description,
				'cover_url' => $album->hasCover() ? $album->getCoverUrl(Image::NORMAL) : null,
				'real_cover_url' => $album->getCoverUrl(Image::NORMAL),
				'tracks' => $tracks
			], 200);
		}
	}