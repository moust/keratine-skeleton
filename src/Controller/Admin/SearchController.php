<?php
namespace Controller\Admin;

use Keratine\Controller\Controller;

use Silex\Application;
use Silex\ControllerProviderInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use ZendSearch\Lucene\Analysis\Analyzer\Analyzer;
use ZendSearch\Lucene\Search;
use ZendSearch\Lucene\Index;

class SearchController extends Controller
{

	public function connect(Application $app)
	{
        $this->container = $app;

		$controllers = $app['controllers_factory'];

		$controllers->get('/', array($this, 'searchAction'))
			->bind('search');

		return $controllers;
	}


	public function searchAction(Request $request)
	{
        $queryString = $request->get('query');

        // count total documents indexed
        $numDocs = $this->get('zendsearch')->numDocs();

        // parse query string and return a Query object.
        // $query = Search\QueryParser::parse($queryString, 'UTF-8');

        $queryTokens = Analyzer::getDefault()->tokenize($queryString, 'UTF-8');

        $query = new Search\Query\Boolean();

        foreach ($queryTokens as $token) {
            $query->addSubquery( new Search\Query\Fuzzy(new Index\Term($token->getTermText()), 0.5), null);
        }

        // process query
		$results = $this->get('zendsearch')->find($query);

        // sort results by score (MultiSearch does not sort the results between the differents indices)
        usort($results, create_function('$a, $b', 'return $a->score < $b->score;'));

        // // paginate results
        // $results = new \Zend\Paginator\Paginator(new \Zend\Paginator\Adapter\ArrayAdapter($results));
        // $results->setCurrentPageNumber($page);
        // $results->setItemCountPerPage($rpp);

        // // fetch results entities
        // $dataResults = array();
        // foreach ($results as $hit) {
        //     $document = $hit->getDocument();
        //     $repository = $this->get('orm.em')->getRepository( $document->getFieldValue('entityClass') );
        //     $dataResults[] = $repository->find( $document->getFieldValue('id') );
        // }
        // $results = $dataResults;

		return $this->get('twig')->render('admin/search.html.twig', array(
            'query'   => $queryString,
            'numDocs' => $numDocs,
			'results' => $results,
		));
	}

}