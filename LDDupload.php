<?php

	//Prepare le retour
	$retour = array();

	if (!empty($_FILES)) {

			//1° On va chercher les pièces
			$lxfml_file_content = file_get_contents('zip://'.$_FILES['file']['tmp_name'].'#IMAGE100.LXFML');

			//If we didn't find any content to analyse, return error message
			if ($lxfml_file_content == "") {

				//Vérifie que fichier LDD. Le 2e option est testé pour utiliser Dropbox via iPhone
				//We already know we don't have a valid file. The error message will help debug what's going on (corrupted ldd file, or not an LDD file)
				if ($_FILES['file']['type'] != "application/x-legoexchangeformat" && $_FILES['file']['type'] != "application/octet-stream") {

					returnPage(array(
						'errorCode' => 415,
						'msg' => "Not an LDD file (IMAGE100.LXFML can't be found or is empty)",
						'errorDetail' => $_FILES['file']['type']
					));

				} else {

					returnPage(array(
						'errorCode' => 416,
						'msg' => "LDD file can't be analyzed (IMAGE100.LXFML can't be found or is empty)"
					));
				}
			}

			//2° Get the image
			$image_file_content = file_get_contents('zip://'.$_FILES['file']['tmp_name'].'#IMAGE100.PNG');

			//Vérifie qu'on a une image
			if ($image_file_content != "") {
				$image = "data:image/png;base64,".base64_encode($image_file_content); //Encode l'image en base64
			} else {
				$image = "";
			}

			$lxfml_xml = simplexml_load_string($lxfml_file_content);
			$lxfml_json = json_encode($lxfml_xml);
			$lxfml_array = json_decode($lxfml_json, TRUE);


			$return_bricks = array();
			$total_bricks = 0;
			$nb_elements = 0;

			//Deux cas: Une seule brique ou plusieurs
			if (!empty($lxfml_array['Bricks']['Brick'][0])) {

				foreach($lxfml_array['Bricks']['Brick'] as $brick_data) {
					processBricks($brick_data, $return_bricks, $nb_elements, $total_bricks);
				}

			} else {
				processBricks($lxfml_array['Bricks']['Brick'], $return_bricks, $nb_elements, $total_bricks);
			}

			//4° Finalise le retour
			returnPage(array(
				'success' => true,
				'nb_bricks' => $total_bricks,
				'nb_elements' => $nb_elements,
				'bricks' => $return_bricks,
				'image'	=> $image
			));

	} else {

		returnPage(array(
			'errorCode' => 404,
			'msg' => "No File found"
		));

	}



	function returnPage($data) {

		//Check for missing stuff. Add default values
		$data['success'] = (isset($data['success'])) ? $data['success'] : false;
		$data['msg'] = (isset($data['msg'])) ? $data['msg'] : "";
		$data['errorCode'] = (isset($data['errorCode'])) ? $data['errorCode'] : 200;
		$data['errorDetail'] = (isset($data['errorDetail'])) ? $data['errorDetail'] : "";
		$data['bricks'] = (isset($data['bricks'])) ? $data['bricks'] : array();
		$data['nb_bricks'] = (isset($data['nb_bricks'])) ? $data['nb_bricks'] : 0;
		$data['nb_elements'] = (isset($data['nb_elements'])) ? $data['nb_elements'] : 0;
		$data['image'] = (isset($data['image'])) ? $data['image'] : "";

		//Retourne
		echo json_encode($data);
		exit;
	}


	/**
	 * processBricks function.
	 *
	 * @access public
	 * @param mixed $brick_data
	 * @param mixed &$return_bricks
	 * @param mixed &$nb_elements
	 * @param mixed &$total_bricks
	 * @return void
	 */
	function processBricks($brick_data, &$return_bricks, &$nb_elements, &$total_bricks) {

		//Cas N°1 : On vérifie si parts est déjà disponible sous brick. Si non, ca veut dire qu'on a une brique avec plusieurs
		//          parts. Donc on prend la couleur de la première sous-part
		if (empty($brick_data['Part']['@attributes']["materials"])) {

			$color = $brick_data['Part'][0]["@attributes"]["materials"];

		//Cas N°2 : On a pas de sous part, on prend donc donc l'unique part sous brick
		//          On ne prend pas la couleur dans la brique, car elle pourrait être fausse ou whatever
		} else {

			$color = $brick_data['Part']["@attributes"]["materials"];
		}

		$return_bricks = AddToBrickList($return_bricks, $brick_data["@attributes"]["designID"], $color, $nb_elements);
		$total_bricks++;

	}

	/**
	 * cleanColor function.
	 *
	 * @access public
	 * @param mixed $colorCode
	 * @return void
	 */
	function cleanColor($colorCode) {

		$retour = array();
		$colorCode_array = explode(",", $colorCode);

		foreach ($colorCode_array as $color) {
			if ($color != 0) {
				$retour[] = $color;
			}
		}

		return implode(",", $retour);
	}


	/**
	 * AddToBrickList function.
	 *
	 * @access public
	 * @param mixed $source
	 * @param mixed $designID
	 * @param mixed $color
	 * @param mixed &$nb_elements
	 * @return void
	 */
	function AddToBrickList($source, $designID, $color, &$nb_elements) {

		$color = cleanColor($color);

		//Créé l'élément au besoin
		if (empty($source[$designID])) {
			$source[$designID] = array();
		}

		//Créé la couleur de l'élément au besoin
		if (empty($source[$designID][$color])) {
			$source[$designID][$color] = 0;
			$nb_elements++;
		}

		//Ajoute un élément de cette couleur
		$source[$designID][$color]++;

		return $source;
	}
?>