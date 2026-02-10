var customerFirst = ['Liam', 'Emma', 'Noah', 'Olivia', 'William', 'Ava', 'James', 'Isabella',
  'Logan', 'Sophia', 'Benjamin', 'Mia', 'Mason', 'Charlotte', 'Elijah', 'Amelia', 'Oliver',
  'Evelyn', 'Jacob', 'Abigail', 'Lucas', 'Harper', 'Michael', 'Emily', 'Alexander',
  'Elizabeth', 'Ethan', 'Avery', 'Daniel', 'Sofia', 'Matthew', 'Ella', 'Aiden', 'Madison',
  'Henry', 'Scarlett', 'Joseph', 'Victoria', 'Jackson', 'Aria', 'Samuel', 'Grace',
  'Sebastian', 'Chloe', 'David', 'Camila', 'Carter', 'Penelope', 'Wyatt', 'Riley', 'Jayden',
  'Layla', 'John', 'Lillian', 'Owen', 'Nora', 'Dylan', 'Zoey', 'Luke', 'Mila', 'Gabriel',
  'Aubrey', 'Anthony', 'Hannah', 'Isaac', 'Lily', 'Grayson', 'Addison', 'Jack', 'Eleanor',
  'Julian', 'Natalie', 'Levi', 'Luna', 'Christopher', 'Savannah', 'Joshua', 'Brooklyn',
  'Andrew', 'Leah', 'Lincoln', 'Zoe', 'Mateo', 'Stella', 'Ryan', 'Hazel', 'Jaxon', 'Ellie',
  'Nathan', 'Paisley', 'Aaron', 'Audrey', 'Isaiah', 'Skylar', 'Thomas', 'Violet', 'Charles',
  'Claire', 'Caleb', 'Bella', 'Josiah', 'Aurora', 'Christian', 'Lucy', 'Hunter', 'Anna',
  'Eli', 'Samantha', 'Jonathan', 'Caroline', 'Connor', 'Genesis', 'Landon', 'Aaliyah',
  'Adrian', 'Kennedy', 'Asher', 'Kinsley', 'Cameron', 'Allison', 'Leo', 'Maya', 'Theodore',
  'Sarah', 'Jeremiah', 'Madelyn', 'Hudson', 'Adeline', 'Robert', 'Alexa', 'Easton', 'Ariana',
  'Nolan', 'Elena', 'Nicholas', 'Gabriella', 'Ezra', 'Naomi', 'Colton', 'Alice', 'Angel',
  'Sadie', 'Brayden', 'Hailey', 'Jordan', 'Eva', 'Dominic', 'Emilia', 'Austin', 'Autumn',
  'Ian', 'Quinn', 'Adam', 'Nevaeh', 'Elias', 'Piper', 'Jaxson', 'Ruby', 'Greyson', 'Serenity',
  'Jose', 'Willow', 'Ezekiel', 'Everly', 'Carson', 'Cora', 'Evan', 'Kaylee', 'Maverick',
  'Lydia', 'Bryson', 'Aubree', 'Jace', 'Arianna', 'Cooper', 'Eliana', 'Xavier', 'Peyton',
  'Parker', 'Melanie', 'Roman', 'Gianna', 'Jason', 'Isabelle', 'Santiago', 'Julia', 'Chase',
  'Valentina', 'Sawyer', 'Nova', 'Gavin', 'Clara', 'Leonardo', 'Vivian', 'Kayden', 'Reagan',
  'Ayden', 'Mackenzie', 'Jameson', 'Madeline', 'Noah', 'William', 'James', 'Logan',
  'Benjamin', 'Mason', 'Elijah', 'Oliver', 'Jacob', 'Lucas', 'Michael', 'Alexander', 'Ethan',
  'Daniel', 'Matthew', 'Aiden', 'Henry', 'Joseph', 'Jackson', 'Samuel', 'Sebastian', 'David',
  'Carter', 'Wyatt', 'Jayden', 'John', 'Owen', 'Dylan', 'Luke', 'Gabriel', 'Anthony', 'Isaac',
  'Grayson', 'Jack', 'Julian', 'Levi', 'Christopher', 'Joshua', 'Andrew', 'Lincoln', 'Mateo',
  'Ryan', 'Jaxon', 'Nathan', 'Aaron', 'Isaiah', 'Thomas', 'Charles', 'Caleb', 'Josiah',
  'Christian', 'Hunter', 'Eli', 'Jonathan', 'Connor', 'Landon', 'Adrian', 'Asher', 'Cameron',
  'Leo', 'Theodore', 'Jeremiah', 'Hudson', 'Robert', 'Easton', 'Nolan', 'Nicholas', 'Ezra',
  'Colton', 'Angel', 'Brayden', 'Jordan', 'Dominic', 'Austin', 'Ian', 'Adam', 'Elias',
  'Jaxson', 'Greyson', 'Jose', 'Ezekiel', 'Carson', 'Evan', 'Maverick', 'Bryson', 'Jace',
  'Cooper', 'Xavier', 'Parker', 'Roman', 'Jason', 'Santiago', 'Chase', 'Sawyer', 'Gavin',
  'Leonardo', 'Kayden', 'Ayden', 'Jameson', 'Kevin', 'Bentley', 'Zachary', 'Everett', 'Axel',
  'Tyler', 'Micah', 'Vincent', 'Weston', 'Miles', 'Wesley', 'Nathaniel', 'Harrison',
  'Brandon', 'Cole', 'Declan', 'Luis', 'Braxton', 'Damian', 'Silas', 'Tristan', 'Ryder',
  'Bennett', 'George', 'Emmett', 'Justin', 'Kai', 'Max', 'Diego', 'Luca', 'Ryker', 'Carlos',
  'Maxwell', 'Kingston', 'Ivan', 'Maddox', 'Juan', 'Ashton', 'Jayce', 'Rowan', 'Kaiden',
  'Giovanni', 'Eric', 'Jesus', 'Calvin', 'Abel', 'King', 'Camden', 'Amir', 'Blake', 'Alex',
  'Brody', 'Malachi', 'Emmanuel', 'Jonah', 'Beau', 'Jude', 'Antonio', 'Alan', 'Elliott',
  'Elliot', 'Waylon', 'Xander', 'Timothy', 'Victor', 'Bryce', 'Finn', 'Brantley', 'Edward',
  'Abraham', 'Patrick'
];
var customerLast = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
  'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'
];
var customerStates = ['AL', 'AK', 'AZ', 'AR', 'CA', 'CO', 'CT', 'DE', 'FL', 'GA', 'HI', 'ID', 'IL',
  'IN', 'IA', 'KS', 'KY', 'LA', 'ME', 'MD', 'MA', 'MI', 'MN', 'MS', 'MO', 'MT', 'NE', 'NV',
  'NH', 'NJ', 'NM', 'NY', 'NC', 'ND', 'OH', 'OK', 'OR', 'PA', 'RI', 'SC', 'SD', 'TN', 'TX',
  'UT', 'VT', 'VA', 'WA', 'WV', 'WI', 'WY'
];
var spBase = window.socialProofBase || '';
var customerProducts = [
  { quantity: '2', image: spBase + 'lib/img/prod1.png', label: '2' },
  { quantity: '7', image: spBase + 'lib/img/6-bottles.png', label: '6+1' },
  { quantity: '4', image: spBase + 'lib/img/3-bottle-buy-btn.png', label: '4' }
];
function updateSocial() {
  var rCustomerFirst = customerFirst[Math.floor(Math.random() * customerFirst.length)];
  var rCustomerLast = customerLast[Math.floor(Math.random() * customerLast.length)];
  var rStates = customerStates[Math.floor(Math.random() * customerStates.length)];
  var rProduct = customerProducts[Math.floor(Math.random() * customerProducts.length)];
  var rAgo = Math.floor(Math.random() * 21) + 1;

  $('#notify-customer').html(rCustomerFirst + ' ' + rCustomerLast);
  $('#notify-state').html(rStates);
  $('#notify-quantity').html(rProduct.label);
  $('#notify-ago').html(rAgo + ' minutes ago');
  $('.custom-notification-image-wrapper img').attr('src', rProduct.image);
  if (rProduct.quantity > 1) {
    $('#notify-multiple').html('s');
  } else {
    $('#notify-multiple').html('');
  }
}

setInterval(function() {
  var  divID = $('.custom-social-proof');
  if (divID.is(':hidden')) {
    $('.custom-social-proof').stop().slideToggle('slow');
    updateSocial();
  } else {
    $(divID).stop().slideToggle('slow');
  }
}, 7000);

$('.custom-close').click(function() {
  $('.custom-social-proof').stop().slideToggle('slow');
});