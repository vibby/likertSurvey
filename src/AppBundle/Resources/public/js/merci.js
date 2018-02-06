
var $collectionHolder;

// setup an "add a subordinate" link
var $addSubordinateLink = $('<a href="#" class="add_subordinate_link">Ajouter un collaborateur</a>');
var $newLinkLi1 = $('<li></li>').append($addSubordinateLink);

$(document).ready(function() {
    // Get the ul that holds the collection of subordinates
    var $collectionHolder = $('ul.subordinates');

    // add the "add a subordinate" anchor and li to the subordinates ul
    $collectionHolder.append($newLinkLi1);

    // count the current form inputs we have (e.g. 2), use that as the new
    // index when inserting a new item (e.g. 2)
    $collectionHolder.data('index', $collectionHolder.find(':input').length);

    $addSubordinateLink.on('click', function(e) {
        // prevent the link from creating a "#" on the URL
        e.preventDefault();

        // add a new subordinate form (see next code block)
        addSubForm($collectionHolder, $newLinkLi1);
    });
});

var $addColleagueLink = $('<a href="#" class="add_colleague_link">Ajouter un collègue</a>');
var $newLinkLi2 = $('<li></li>').append($addColleagueLink);

$(document).ready(function() {
    // Get the ul that holds the collection of colleagues
    var $collectionHolder = $('ul.colleagues');

    // add the "add a colleague" anchor and li to the colleagues ul
    $collectionHolder.append($newLinkLi2);

    // count the current form inputs we have (e.g. 2), use that as the new
    // index when inserting a new item (e.g. 2)
    $collectionHolder.data('index', $collectionHolder.find(':input').length);

    $addColleagueLink.on('click', function(e) {
        // prevent the link from creating a "#" on the URL
        e.preventDefault();

        // add a new colleague form (see next code block)
        addSubForm($collectionHolder, $newLinkLi2);
    });
});

function addSubForm($collectionHolder, $newLinkLi) {
    // Get the data-prototype explained earlier
    var prototype = $collectionHolder.data('prototype');

    // get the new index
    var index = $collectionHolder.data('index');

    var newForm = prototype;

    // increase the index with one for the next item
    $collectionHolder.data('index', index + 1);

    // Display the form in the page in an li, before the "Add a subordinate" link li
    var $newFormLi = $('<li></li>').append(newForm);
    $newLinkLi.before($newFormLi);
}
