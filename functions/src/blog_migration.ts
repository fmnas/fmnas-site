/**
 * @license
 * Copyright 2025 Google LLC
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
import { BlogPost } from './fmnas.js';
import { Firestore } from '@google-cloud/firestore';
import { logger } from './logging.js';
import { HttpFunction } from '@google-cloud/functions-framework';
import mysql from 'mysql2/promise';

const posts: BlogPost[] = [
	{
		path: 'single-kitten-syndrome',
		title: 'Single Kitten Syndrome – yoo haz it?',
		date: '2011-04-18T03:13:25Z',
		body: `{{>aside i='0' caption='Crazy Kitteh'}}
		
Kittens are like hyper-wound balls of energy, and work off their energy best when having another animal to play with. Two kittens around the same age do well together because they are both at the same stage of development, and both want to POUNCE and TUSSLE and ROLL and RACE AROUND.

In the absence of a second kitten, one kitten will turn their attentions either to another animal in the house – and hopefully that animal will enjoy the activity – OR in the absence of a responsive animal, will turn their attentions to their people and surroundings.

When I was 8 I begged and begged my parents for this one fabulous kitten… they relented, and I had my first Maine Coon, wow! He was an awesome cat and lived for 24 years, BUT his kittenhood in a home with no other kittens to wrestle with and pounce on was a HOLY TERROR!

He would race down the hall, up one side of the living room drapes, tear across to the other side, then slide down with his claws SCREEEEEEEEEEEEEEEEing through the drape material. Then it was off for a bounce and a flying squirrel leap onto the wall, with a slow slide through the wallpaper with the claws. Then three sideways leaps and a POUNCE and CHOMP on whatever ankle happened to be walking past. You get the idea.

We found out later from our vet that he was suffering from “single kitten syndrome” – he needed to work out his kitten energies on a member of his own species rather than on anyone stumbling to the bathroom at 2:00 a.m. Just something to consider, and maybe talk it over with your vet.

Can’t help it, you only get to have one kitten? You can help simulate a kitten buddy by putting a plush toy on the end of a sturdy stick, and using it to “wrestle” with your kitten. Another plush toy in kitty’s favorite sleeping spot can substitute for a cuddle buddy. Finally, playing with wand-style toys encourages your kitten to run and leap and work off some of that endless kitten energy.

One important tip: do NOT use a hand or foot to wrestle with your kitten. Sure, it’s adorable when your little 2-pound fluff-ball is ferociously attacking your fist – everyone laughs! Fast forward a couple of years, and you now have a beefy 16-pound kitty, who learned that attacking your body parts is both fun and acceptable. Not laughing now, are you? Ow.

[Here is some additional information](https://www.pawschicago.org/why-young-cats-should-be-adopted-in-pairs/) from PAWS Chicago; they feel more strongly about the issue than we at Forget Me Not do, and we trust you to decide what is best for you and your family.`,
		author: 'Kim',
		photos: [
			{
				path: 'blog/wp-content/uploads/2011/04/Kiki-Ko2.jpg',
				sizes: []
			}
		],
		comments: [
			{
				author: 'Mary',
				date: '2011-04-19T13:38-07:00',
				body: `I remember Sneakers! 🙂 And that story, by the way.

Excellent point about not using your fist, or foot to wrestle with the baby animal – goes the same with dogs too! Might be cute when a pup, but as an adult…ouch. And, family/guests in the house may not be so welcoming of a cat or a dog that attacks in play.`,
				children: []
			},
			{
				author: 'Carmen Flores',
				date: '2016-09-18T00:03-07:00',
				body: `For me is not complicated, we live in siniors apartment, everybody, have a cat or dog , friendly s pets. Lady’s that know how to train and deal with kytis will help me and show me how to deal with my baby companion, is other cats.`,
				children: []
			},
			{
				author: 'Carmen Flores',
				date: '2016-09-18T00:08-07:00',
				body: `With Love everything is possible.
And I have so much to give to my Kitty
My dream is to enjoy be around him share love , my time care .
Provide safe and happy home for my for ever cat .`,
				children: []
			}
		]
	},
	{
		path: 'ask-the-shelter-what-is-a-stray-dog-and-what-do-i-do-if-i-find-one',
		title: 'ASK THE SHELTER: What is a “stray” dog, and what do I do if I find one?',
		body: `This is an excellent question, and one that isn’t as simple to answer as you may think.

Whether a dog is a stray or not sometimes depends on the laws in your particular area.

In Ferry County, only the city of Republic has a leash law.

<strong>If you are within Republic city limits</strong>, and spot an unaccompanied dog off leash, that dog is a stray dog. If you find a stray dog in the city limits, you should:

1. Check the dog for a collar and ID or licensing tags; the city also has a dog licensing law, so all dogs that live within city limits should have a license tag on their collars.

2. If there is a collar with tags, but no identification that provides the owner’s contact info, you can find the owner’s info by calling City Hall with the dog license tag information, OR by calling the veterinarian listed on the dog’s rabies tag and providing the rabies tag number.

3. If there is no identification, you may want to try using social networking to find the dog’s owner. Facebook groups like [Ferry County Exchange](https://www.facebook.com/groups/220223288022111/), [99166](https://www.facebook.com/groups/99166FerryCountyRepublic/), or [Lost/Stolen/Found Animals](https://www.facebook.com/groups/237656749685133/) often have rapid success at returning the dog to its owner quickly. Even if the owner is not on Facebook, their friends, relatives, or neighbors may be, and can recognize the dog and contact the owner. If you are not on Facebook, you can send a picture of the dog to Forget Me Not (info at forgetmenotshelter dot org) and we will be happy to post on Facebook for you.

4. If all attempts to locate the owner fail, you can contact Republic Police Department to impound the stray; they will take it to Konz Veterinary, which has an impound contract with the city, where it will be held for 3 days to give the owner a chance to find it.

<strong>If you are outside Republic city limits</strong>, things are a little bit murkier due to the lack of leash or licensing laws.

If the dog seems to be moving with purpose along the road, and he is not creating an overly dangerous situation for drivers in the area, there is a good chance he is not a stray dog, but is simply a local country dog off on his daily explorations, possibly heading home. If you don’t see the dog in the same place a second time, he has probably gone home. It is not illegal for a dog in Ferry County to wander to his heart’s content, as long as he is not chasing livestock or causing accidents.

If the dog is wandering aimlessly in one set area along a road, looking nervous or appearing to be waiting for someone, then she may be lost – a “stray” dog – and in need of some assistance finding her way home.

If the dog is found on your property and doesn’t leave within a couple of hours, it is also safe to assume he is a stray dog.

When finding a stray dog within the county, and there is no collar with ID, the best procedure is:

1. Bring the dog to a safe place with you if you are able to do so. If you cannot bring the dog with you, make a note of exactly where the dog is (mile markers, cross streets, nearby houses or landmarks) so someone would be able to find the location. Take photos if you can.

2. Call the shelter 509-775-2308 or email info at forgetmenotshelter dot org to report the stray. Email reaches the volunteers faster than phone calls, but either method will work. If you email, please include a couple of photos of the dog if you are able. When the shelter receives photos by email, we can start searching for the dog’s owner immediately, and sometimes can connect you with the owner directly, which eliminates the need to have law enforcement impound the dog, or to have the dog brought to Forget Me Not.

3. If the owner can’t be found quickly, the Forget Me Not volunteers will arrange an appointment for you to bring the dog in to the shelter; we will also take care of notifying the Ferry County Sheriff so they can impound the stray, we will check the dog for a microchip, and we will do flyers and online ads to try to locate the dog’s owner.

Please only pick up the dog if you are able to hang onto her until arrangements can be made for her to come into the shelter. Forget Me Not is primarily staffed by volunteers, and has no set operating hours; we will need to find a volunteer who can make an appointment with you to go to the shelter and do intake on the dog, so it can sometimes take 24-48 hours from the time you pick up the dog to the time it comes to the shelter. If you cannot care for the dog for up to 48 hours, please just take photos, make a precise notation of the exact location of the dog, and call or email the shelter with the information so we can post the dog’s photo and location online and either find her owner, or find a volunteer in the community who can pick her up and bring her to the shelter by appointment.

NEVER pick up a stray dog and just decide to keep it. That is considered theft of property, which *is* illegal. Even if a stray dog is emaciated, injured, or appears to have been beaten or neglected, you don’t know the full story. It could be a child’s beloved pet that was lost 2 months ago on a stop for gas in Republic, and has been wandering all this time hoping to find its family again. Generally, if the dog is neglected or abused, the owner will not want to pick it up from the shelter and pay the impound fees/go on record as the owner. If the dog is a beloved pet that has been missing, the owner generally gets to the shelter as fast as humanly possible, and there is a joyful, tearful reunion.

If the owner is not located, or doesn’t pick up their dog, Forget Me Not will then place the stray up for adoption, after making sure it is spayed/neutered, microchipped, treated for any parasites, and up to date on its vaccinations. You can then apply to adopt the dog back and become the dog’s new (and forever) legal owner.`,
		author: 'Kim',
		date: '2014-06-01T13:31-07:00',
		photos: [],
		comments: []
	},
	{
		path: 'ask-the-shelter-is-forget-me-not-animal-shelter-a-no-kill-shelter',
		title: 'ASK THE SHELTER: Is Forget Me Not Animal Shelter a "No-Kill" shelter?',
		body: `Yes, Forget Me Not Animal Shelter is a no-kill shelter. After 10 years of operating “as if” we were no-kill, the Board of Directors voted this year to officially become part of the No-Kill Nation movement.

What that means is, no healthy, adoptable pet will ever be euthanized for lack of space or length of time at the shelter.

Pets with treatable medical conditions will receive appropriate treatment and be placed up for adoption, as long as they have a reasonable likelihood of good quality of life post-treatment. Recent examples of these pets would be Sammi (fka Cuckoo) and Dunlin, senior cats that received radioactive iodine treatment for hyperthyroidism, and Freckles, a dog who is in the process of receiving appropriate surgeries and physical therapy to heal from injuries received in his prior home.

Being a no-kill shelter does not mean we will leave a pet suffering. Recently, Vader, one of the senior shelter kitties, was found to have an enormous, inoperable tumor in his abdomen. We knew he was suffering, because he would growl every time we picked him up. Part of our responsibility to the shelter pets is providing them the loving care they need, even when that means relieving their suffering with humane euthanasia. When that is the best option for a pet, one of the volunteers is there with them so they have comfort and love during their last moments.

The other exception for no-kill shelters is for animals that are too dangerous or aggressive to safely house and rehome. In addition to our responsibilities toward the pets in our care, we have a responsibility toward the community, and toward our volunteers and staff. Forget Me Not generally will not accept any aggressive pets into the Happy Homes adoption program.

There are two things that will enable Forget Me Not to thrive as a no-kill shelter:

1. Partnerships with other shelters and rescues. We have already reached out to several shelters and rescues around the state, to have a safety-net in place in case of overcrowding or long-time pets that need some new exposure to find their forever homes.

2. Foster homes, local and across the state, that can help us in times of crisis, or with pets that need some special TLC. This is where YOU can come in! We would like to create a “go-to” list of available foster homes, and can tailor your foster pets according to your preferences, and place a pet with you only as often as you prefer.

Want to sign up to be a life-saving foster home? Give us a call at 509-775-2308 (leave a message, we’ll call you back); [message us on Facebook](https://www.facebook.com/ForgetMeNotAnimalShelter); or send us an email: <a data-email="info"></a>.`,
		author: 'Kim',
		date: '2014-05-26T04:33:14Z',
		photos: [],
		comments: []
	},
	{
		path: 'what-is-a-professional-breeder-and-why-do-i-executive-director-of-an-animal-shelter-support-them',
		title: 'What is a “professional breeder” and why do I, Executive Director of an animal shelter, support them?',
		body: `People are sometimes shocked to learn that I personally support and encourage professional, registered breeders.

The first thing to understand is the definition of a professional, registered breeder. This is not a “puppy or kitten mill,” nor is it someone who just couldn’t resist letting their wonderful, adorable dog or cat have just one litter before spaying, nor is it someone who makes their living by breeding random dogs and cats into cute combinations.

This is what I consider to be a professional, registered cat or dog breeder:

1. REGISTERED with a national breeder organization such as AKC or CFA
2. SPECIALIZING in a recognized purebreed (or two), and breeding for conformation and health
3. RAISING all the puppies and kittens in a safe and stimulating home environment, with healthy, vibrant, happy sire(s) and dam(s) on site, and available for potential buyers to meet
4. PLANNING for every single litter well in advance, with the primary goal of maintaining the breed’s standard, and usually having a waiting list for the puppies or kittens; the professional breeder never has more litters than they can properly socialize and care for at any one time
5. GUARANTEEING the health of their litters, by spaying/neutering any dogs or cats that carry any sort of genetic health or conformation problem, and beginning vaccination of every litter at the proper time. All adult dogs and cats receive regular examinations and veterinary care.
6. SCREENING all potential buyers to ensure they understand the mental, physical, and emotional needs of the breed they are considering, and that they have the commitment and means to care for their new pup or kitten
7. PROVIDING all paperwork and pedigree for several generations, along with a contract of sale
8. NURTURING every litter until at least 8 weeks of age, and preferably 12 weeks of age
9. MAINTAINING contact with the owners of each littermate
10. ACCEPTING back into the home any pup/dog/kitten/cat that originated with the breeder, at any time during that animal’s lifetime, for any reason

This level of commitment to a breed requires a lot of effort, and a lot of money. Ask any registered professional breeder, and they will be the first to tell you, they do NOT “get rich” by breeding; it, much like volunteering for an animal shelter, is primarily a labor of love.

How do you know you are NOT dealing with a professional, registered breeder?

* Are you getting a puppy or kitten from a box outside a big box store, or on a sidewalk somewhere? You are NOT dealing with a professional, registered breeder.
* Does the breeder refuse to allow you to meet the parents or come to the home where the pups or kittens are being raised? You are NOT dealing with a professional, registered breeder.
* Did you find your puppy or kitten from a handwritten flyer at the grocery store? You are NOT dealing with a professional, registered breeder.
* Does your breeder seem to specialize in several different breeds, including “designer” breeds like the “Yorkihuapoo” or “Dachspitterrier”? You are NOT dealing with a professional, registered breeder.
* Did you get your new pup or kitten without any sort of contract or without receiving any sort of written return guarantee? You are NOT dealing with a professional, registered breeder.
* Does the breeder not care at all whether you plan to spay/neuter, or whether the pup or kitten is genetically proven to be good breeding stock? You are NOT dealing with a professional, registered breeder.

I’m sure there are dozens of additional red flags, which I invite both professional breeders and those who may have been taken in by a puppy mill or backyard breeder to share in the comments.

Without dedicated breeders using their own time and resources to maintain (and better) breed standards, our world would eventually consist of primarily 40-50 pound black and brown short-haired dogs, and the basic Domestic Shorthaired Cat in various colors. I believe we need professional registered breeders to ensure that the chihuahua and the mastiff, the border collie and the Labrador retriever, the Persian and the Bombay, remain a part of our world.

Those who are deliberately breeding without providing proper care, without maintaining breed standards, and without screening and following up with every single home… those who are doing it to make $200 each on puppies you feed last night’s leftovers to, mixed with whatever the cheapest dog food you could find, without vaccinating or health-checking… you know how I feel. Shame on you.

What about those who are not deliberately breeding, but through ignorance or poor timing have an “oops” litter?

If you cannot raise your litter by the standards a professional breeder would have (see above), and cannot take the time and effort to find every pup or kitten the absolute best possible home, then please, please, ask a shelter or rescue to take in your litter and find them homes. At Forget Me Not, we take in “oops” litters from all around Ferry County; we will provide food during the time the litter is with your mother cat or dog; we can often take the litter *and* the mother in, returning the mother after the litter is weaned; we spay/neuter, vaccinate, deworm, and microchip every pup or kitten prior to placement; we search for homes far and wide, and will take back a pet if its placement doesn’t work out.

All we require is that you allow us to spay the mother animal after the litter is weaned.

There is a need for professional, registered breeders… and until there are no longer any UNprofessional breeders, no more backyard breeders or puppy/kitten mills, and no more “oops” litters… there will also be a need for shelters and rescues.

*The above article reflects the views of the author, and may not reflect the views of the board, staff, or volunteers of Forget Me Not Animal Shelter.*`,
		author: 'Kim',
		date: '2013-07-23T03:54:59Z',
		photos: [{
			path: 'blog/wp-content/uploads/2012/11/Sunny-150x112.jpg',
			sizes: []
		}],
		comments: [
			{
				author: 'Anita Sanders',
				date: '2013-07-22T21:13-07:00',
				body: `Outstand post, Kim. Very well thought out and understanding the hows and whys of both the Shelters and the Breed Rescues and why we need reputable Breeders to ensure their Breed continues to thrive in spite of the bab’s and, worse yet, the puppy mills.

People, please! Before you encourage both of the above to keep breeding by buying a cheap puppy, look to rescue from a shelter or find one on Petfinder. If your heart is set on a bre3ed specific puppy, then save your money, do your homework, and pay the fair price up front instead of in medical care later. You truly get what you pay for.

Anita Sanders
Rainy Days Mastiffs
Long Time Rescue Transport Coordinator`,
				children: [
					{
						author: 'Forget Me Not - Kim',
						date: '2013-07-26T14:44-07:00',
						body: `Thanks, Anita; you make another excellent point. If someone is REALLY wanting a particular purebred, then it should be a planned purchase, done after the benefit of research, contemplation, and saving for the purchase. Adding a family member that will be with you from 10-25 years (and some cats live even longer than that!), whether via a reputable breeder or via adoption from a shelter or rescue, is not a decision to be made on a whim.`,
						children: []
					}
				]
			},
			{
				author: 'Carlene Joy',
				date: '2013-07-23T06:58-07:00',
				body: `Great Post Kim!`,
				children: [
					{
						author: 'Forget Me Not - Kim',
						date: '2013-07-26T14:40-07:00',
						body: 'Thanks, Carlene!',
						children: []
					}
				]
			},
			{
				author: 'MoZeu',
				date: '2013-07-25T05:44-07:00',
				body: `I feel much better about the fact that I seem unable to let go of my longing for a full-fledged spoo (and am willing to find a real breeder and spend the time and money involved) instead of a rescue mutt. I feel so guilty about that, but I simply adore them so much. They are just magnificent, intelligent, graceful dogs.`,
				children: [
					{
						author: 'Forget Me Not - Kim',
						date: '2013-07-26T14:40-07:00',
						body: `MoZeu, all we volunteers at the shelter fell madly in love with the poodles we cared for briefly last year, and your longing is completely understandable – they are a special breed, for sure.`,
						children: []
					}
				]
			},
			{
				author: 'Monica',
				date: '2013-07-25T19:35-07:00',
				body: `Excellent post. A pet buyer really has to think about the motivations of the person selling them their future family member. If it’s money, that’s not who you want to support.

I would hesitate to recommend a “professional” breeder though, if breeding is their sole profession or job I’d be concerned. Most reputable breeders think of it as only a lucky-if-I-break-even type of hobby.`,
				children: [
					{
						author: 'Forget Me Not - Kim',
						date: '2013-07-26T14:38-07:00',
						body: `Thanks, Monica – you are right, from what I have heard it’s a money-losing proposition if you’re doing it with the best interests of the breed and your individual animals in mind!`,
						children: []
					}
				]
			},
			{
				author: 'Linda',
				date: '2013-07-26T06:13-07:00',
				body: `This could be excellent but there are some significant flaws in your otherwise appreciated post. I breed a rare breed. To have one or more stud dogs on my property and using those dogs exclusively – so people can meet them – would be disastrous to the health of our breed. I have used semen from dead dogs imported from Sweden, sent girls to across North America, in order to find the best match in health, personality and conformation that is important to my breed. This comes at great expense. Buyers need to trust that I do this FOR the puppies and for them. I personally would run away from any breeder who bred to dogs on their property most of the time.
Also, I keep puppies as long as they need. Many of my medium-sized breed are very ready to head out by 8 weeks. Others need more time with their dam & other adults, plus me, to develop better bite inhibition or overcome silly puppy fears. 12 weeks is no more a magic number for a great puppy than 49 days, the former magic number. Smaller breeds or large breeds will be different.
And breeding flaws…sometimes breeders are between the devil and the deep blue sea. In another breed I played briefly (very rare), hip dysplasia is everywhere. But these dogs rarely hurt. And if the breeders didn’t breed an occasion “mild” asymptomatic dog they wouldn’t have a breed. Their gene puddle would dry up in a few short years.
Just a little food for thought. Keep up the good work.`,
				children: [
					{
						author: 'Forget Me Not - Kim',
						date: '2013-07-26T14:35-07:00',
						body: `Thanks, Linda – excellent points, there are always exceptions to every rule. As long as the exceptions are being made by a breeder who is knowledgeable about the breed, the benefits of the exception, and every one of their individual pups, there is no need to worry. It’s the exceptions being made by people who “want to breed my dog with my neighbor’s dog because they are both so cute” and then start selling 6 week old puppies on Craigslist that make my hair stand on end.`,
						children: []
					}
				]
			},
			{
				author: 'Ann',
				date: '2013-07-26T06:20-07:00',
				body: `Kim, Nice article. Thank you for writing On the topic. I would like to point out a couple of misconceptions.

Breeders are not registered with the AKC, the UKC, etc.. These organizations Register dogs not breeders. The person should go look for a breeder who is a member of the national breed club. These clubs have a code of ethics I wish third-grader agrees to abide.

Secondly, a good reader will rarely have both the sire and dam on site. Today we are able to import semen from dogs all over the world. This way they breeder can select the dog that desk complements the pedigree of the female. It also widens the gene pool of theirchosen breed. They can also be sure that the sire has all of its health clearances. A good reader will not breed the same two dogs over and over. That is just a sign of someone trying to make money off of their dogs..

A Professional Breeder. Is someone who breeds dogs for a living. These are the people of puppy mills. A responsible breeder does it for the love of their breed. If a litter is planned and bred and raised correctly very rarely does the breeder break even. They Are involved with their dogs as companions, Showing their dogs at conformation talkshows and at performance events such as agility, rally and obediences.

They also rarely breed their dog more than once in a year. They want to give the dogs body time to rest and recover. Most will put a limit on the number of litters a dog will have in its lifetime. The dog has to pass health test and those test results are registered with a national organization such as the orthopedic foundation of America. They would never breed a dog of questionable temperaments. Responsible breeders understand that their dogs first job in life is to be someone’s loving companion. Most responsible breeders will not ship their dogs.

Thank you again for the well-written article. Please do not take my comments as a criticism but just as a way to inform others.
Ann`, children: [
					{
						author: 'Forget Me Not - Kim',
						date: '2013-07-26T14:37-07:00',
						body: `Thanks so much, Ann! I, of course, am not a breeder and really appreciate your clarifications and distinctions.`,
						children: []
					}
				]
			},
			{
				author: 'Deb Cooper',
				date: '2013-07-26T20:21-07:00',
				body: `Kim – You wrote an amazing article!! Wish every shelter manager operated the way your group appears to!! I’ve run San Antonio Pug Rescue for almost 10 years and I can’t even count the Pugs we’ve gotten from backyard breeders and/or, as you call them, oops litters. We have one area of town that almost every year we get at least 1 or 2 pregnant females from. Everyone of them has tested high positive for heartworms and every one of them has delivered from 2 to 8 purebred Pug puppies!! We KNOW there’s a backyard breeder over there we’ve just never managed to identify who it is!! We’ve also over the years, along with 2 other Pug rescues taken many from the puppymills that are in several states around us. The conditions these poor animals are raised in are absolutely the most disgusting thing I’ve ever seen and I would love nothing more than to see every last one of the shut down!!

Keep up the wonderful work y’all are doing at Forget Me Not!!!

Deb Cooper`,
				children: [
					{
						author: 'Forget Me Not - Kim',
						date: '2013-07-28T13:01-07:00',
						body: `Thanks, Deb – and thanks for all you do! In a perfect world, no one would give money to a BYB and they would lose the financial incentive to continue their puppy mills. One step at a time!`,
						children: []
					}
				]
			},
			{
				author: 'Anita Sanders',
				date: '2013-08-11T23:15-07:00',
				body: `People, please keep in mind that Kim is using terms she’s familiar with to describe responsible, ethical Breeders. She doesn’t necessarily mean those that are listed with USDA as “kennels”. Rather, those that have proven their merit towards their Breed and to the pet community itself. Again, I am glad for her and Shelter Directors like her that most times put their own life on hold to help the unfortunate. Sarah, I get it and wish we could fix it, but in the 20+ years I’ve been involved with rescue, there never seems to be an end to it. 🙁
Anita`, children: []
			}
		]
	},
	{
		path: 'ella-donation',
		title: 'Ella gives back! How one shelter adoptee is making a big difference',
		body: `Meet Ella, adopted from Forget Me Not in summer 2004. This is Ella now, with her parents Megan Lyden and Sam Mann:

{{>figure i='0' caption='Megan Lyden, Ella, Sam Mann'}}

And this is how Ella looked when she first arrived at her Forget Me Not foster home in 2004, a skinny, bedraggled stray with a coat full of burrs and grass seeds:

{{>figure i='1' caption='Ella in 2004'}}

Ella was Forget Me Not’s very first long-distance adoption (her shelter ID number was 15, meaning she was the 15th pet taken into the Happy Homes Adoption Program)! Megan and Sam made the 12-hour round-trip drive to pick her up, and she has been one happy dog ever since. Here she is playing dress-up:

{{>figure i='2' caption='Ella in disguise'}}

…and here she is, showing off her fancy moves:

{{>figure i='3' caption='Ella agility'}}

Ella came to visit Forget Me Not recently, bringing her parents Megan and Sam… and a donation in their names from Microsoft, Sam’s employer. It turns out that Sam was part of a team that won a Microsoft Technical Recognition Award (this is a VERY impressive feat), for creating the Kinect skeletal recognition system (if you have an Xbox, you probably know what that is). As part of this prestigious award, Sam and Megan got to choose a nonprofit to receive $50,000 – and Forget Me Not is honored, humbled, and oh so excited to be their choice!

Here is Ella (with an assist from Megan and Sam) presenting the check to Forget Me Not’s Board of Directors and Executive Director:

{{>figure i='4' caption='L-R: Jill Heming, Secretary; Sarah Wilson, Treasurer; Megan Lyden; Samuel Mann; Ella; Laura Brown, President; Kim Gillen, Executive Director; Lin Seynave, Vice President; Colleen Randle, Board Member'}}

Forget Me Not will be using this funding to pay off the mortgage on the property; we also recently were granted a State of Washington 501 (c)3 humane society property tax exemption, so with no mortgage and no taxes, we are confident Forget Me Not will be here forever (or at least until there are no more unwanted pets – we can dream).

While the size of the donation is amazing and inspiring, the fact that Sam and Megan chose Forget Me Not is what matters the most to all of us. Whenever an adopter comes back to us after their adoption – with a donation, be it $5 or $50,000, or to adopt another family member, or to volunteer, or just to visit – we are so grateful to know they feel that continued connection to Forget Me Not. Even though we have well over 1,000 adopters in the past 8 years, we really do feel like each one is a member of our family. We hope to continue to receive our annual holiday photo of Megan, Sam and Ella for many more years!`,
		author: 'Kim',
		date: '2012-06-14T18:44-07:00',
		photos: [
			{ path: 'blog/wp-content/uploads/2012/06/Ella5.jpg', sizes: [] },
			{ path: 'blog/wp-content/uploads/2012/06/Sisterearly.jpg', sizes: [] },
			{ path: 'blog/wp-content/uploads/2012/06/Ella.jpg', sizes: [] },
			{ path: 'blog/wp-content/uploads/2012/06/Ella2.jpg', sizes: [] },
			{
				path: 'blog/wp-content/uploads/2012/06/TWO-Jill-Sarah-Megan-Sam-Laura-Kim-Lin-Colleen-Ella-1024x769.jpg',
				sizes: []
			}
		],
		comments: [
			{
				author: 'jeanne barrett',
				date: '2012-06-18T19:11-07:00',
				body: `I know Ella and her people well. She is my dear canine person, very happy with Sam and Megan, and a delight in every way! Thank you for all the good work you at Forget Me Not do!
Ella is evidence that rescued dogs can recover from a tough start and be great family members!`,
				children: [
					{
						author: 'fmnas',
						date: '2012-06-22T19:49-07:00',
						body: `Thank you for your kind words, Jeanne, and for your support and for being one of Ella’s best buddies! You are right, with caring and committed adopters, a rescued dog can become a one in a million family member. They are always so grateful, and they never, ever take their beloved people or their new life for granted. People could learn so much from dogs!`,
						children: []
					}
				]
			}
		]
	},
	{
		path: 'chaining',
		title: 'Chaining/tethering – not the right solution',
		body: `{{>aside i='1' caption='Neck damaged by long-term chaining'}}

This is one clear reason why a dog should not be chained for long periods of time (more than an hour is too much); fencing, kenneling, or keeping the dog inside are all better solutions than chaining/tethering.

It isn’t difficult to housetrain a dog so they can stay safely inside, or to crate train a dog… a moderately sized fenced portion of the yard or a humanely sized chain link dog kennel isn’t too much to ask if the dog needs to stay outside.

If all else fails, closing off a mud room or bathroom for the dog’s comfort when home alone can be a simple solution. Anyone who just can’t take the time to do one of those things really doesn’t need a dog. Doesn’t your dog deserve better than this?

This beautiful girl will be going up for adoption soon; chainers need not apply.

{{>figure i='0'}}`,
		author: 'Kim',
		date: '2012-06-09T11:46-07:00',
		photos: [{
			path: 'blog/wp-content/uploads/2012/06/Dolly.jpg',
			sizes: []
		}, {
			path: 'blog/wp-content/uploads/2012/06/Dollyneck.jpg',
			sizes: []
		}],
		comments: [
			{
				author: 'Lin seynave',
				date: '2012-06-09T20:48-07:00',
				body: 'Poor baby. What’s its name and breed?',
				children: [
					{
						author: 'fmnas',
						date: '2012-06-10T21:51-07:00',
						body: 'She is Dolly, a Plott Hound/Treeing Walker Coonhound mix, and her listing just went up! Sweet girl, has to learn everything about everything (especially love).',
						children: []
					}
				]
			}
		]
	},
	{
		path: 'love-hurts',
		title: 'Cat behavior: Love Hurts! When your cat says “FANGS for the new home!”',
		body: `We’ve all seen examples of the “perfect” cat, who will curl up with you on the couch and let you pet and brush him for hours, and who sleeps peacefully at the foot of your bed all night long.

In reality, cats are individuals just like their humans, and they have their own ideas about… well, just about everything!

One of our fabulous recent cat adopters has a question about their new family member’s “biting” behavior:

*“…how can we keep her from biting when she gets overly excited. At night when we are sleeping she will jump up and bite our hands to wake us up to pet her, or if she’s sitting by us and we are petting her, she gets so excited that she will randomly attack our hand… why does she do this and how can we make her stop?”*

There are two separate issues at play (no pun intended). First, the waking up for play and attention:

Although we all try to get our cats to adjust to the human sleep schedule, in nature, cats are nocturnal. Many cats adopted from shelters have embraced their nocturnal nature by the time they find a forever home, and it can take some time and discipline to get them to accept the humans’ idea of sleep time.

The most important thing in this situation is to not reward the cat’s unwanted nighttime behavior by giving them what they want. At the cat’s first attempt to wake you, say “No!” and push the cat gently away, then tuck those arms under the covers so she can’t continue to nip at your hands.

The next “escalation” of nighttime training can be the addition of a spray bottle of water within easy reach of the bed – this works best if there are two people, so that the one who is not being approached to play can squirt kitty “out of nowhere” so she doesn’t associate the squirting with a person but with her own behavior. You want her to learn “when I wake up my person, it rains on me” rather than “my person is a mean old water monster”! Some people have success with this method but choose to replace the squirt bottle with a burst of compressed air. Whichever deterrent you prefer, it is important to use it only while the cat is actively nipping or pawing to wake you up; you don’t want to teach the cat to avoid the bed, just to avoid the unwanted behavior.

If in-bed deterrents aren’t appealing to you, or don’t work, the final suggestion is to remove the cat from the bedroom the first time she wakes you. Just calmly say “No” as you pick kitty up and place her outside the bedroom door, which you then close. The first nights you try this, you may be subjected to meowing, pawing at the door, and rattling of the doorknob… earplugs can work wonders.

Cats are more trainable than many people think – they are usually quite smart, and will make the connection between “I nip my sleeping person’s hand” and “I am – spritzed, or puffed, or evicted” fairly quickly. The key is consistency – NEVER reward unwanted behavior by giving the cat what they want (which is generally attention), and ALWAYS use the same techniques to deflect or deter the unwanted nighttime wake-up call.

Now, on to the second issue – biting during petting or grooming sessions:

This sort of biting is generally called “overstimulation biting” and occurs when the cat’s humans don’t understand or pay attention to the cat’s body language. In simplistic terms, think of petting or grooming as an activity that fills up the cat’s affection cup. Once the cup is full, the cat becomes annoyed at the extra affection spilling out over the top, and begins to say “enough already” by some or all of these signals: flattened ears, swishing tail, twitching back, narrowed eyes, dilated pupils. Once you see any of those signs, it’s time to calmly disengage from petting or grooming, to give kitty time to absorb the affection stored in the cup.

The most common areas of the cat that lead to overstimulation are the back/tail and the belly; they usually will tolerate more petting around the head and neck, and chin scritches are welcome too.

If you want to increase your individual cat’s tolerance so you can have longer petting or grooming sessions, it can be done with some patience and dedication.

First, you need to find out how long on average you can pet your cat before she starts showing any signs of overstimulation. Let’s say she makes it for 3 minutes before that first tail twitch starts. Now that you know she has a 3-minute tolerance, spend several days petting her for only 2.5 minutes, so your petting sessions always end with her happy and content.

After several days of successful petting sessions, you can gradually increase the length of time for the sessions – add 20 or 30 seconds each day or two so she learns to tolerate gradually longer sessions. If she does get overstimulated and bite, say “No bite!” and gently remove your hand and end the petting session; go back to the shorter length for a few more days before gradually increasing the time again.

There are also cats who are simply “love nippers” – they really can’t help it, they want to gently grab a little bit of their favorite human between their teeth, without any overstimulation issues at all. While it seems a bit weird, love nips are actually a great compliment – your kitty adores you! Just use the training tips to let her know humans don’t like bites, and she’ll get the hint.

Here is my cat, George, letting me know it’s time to STOP:

{{>figure i='0' caption="George's lightning-fast overstimulation response"}}

{{>figure i='1' caption='George says "stop petting me. NOW!"'}}`,
		author: 'Kim',
		date: '2011-09-05T15:28-07:00',
		photos: [{
			path: 'blog/wp-content/uploads/2011/09/Lovehurts2.jpg',
			sizes: []
		}, {
			path: 'blog/wp-content/uploads/2011/09/Lovehurts.jpg',
			sizes: []
		}],
		comments: [
			{
				author: 'Marissa',
				date: '2011-09-05T16:41-07:00',
				body: `Thanks so much for getting back! The ideas for night time are wonderful, but the biting while playing or petting is not what she does. She bites us when we STOP petting her, it’s as if we haven’t given her enough and she wants more…. even if we’ve been petting her for 30 minutes LOL. She is constantly purring, she loves attention so much, and that’s when the attack happens, once she attacks, she then grips our wrist and licks the spot she nipped on, and usually will butt her head against our faces. There are never signs of annoyance, always signs of affection when she does this. It doesn’t hurt us, just catches us off guard.`,
				children: [{
					author: 'fmnas',
					date: '2011-09-06T14:03-07:00',
					body: `Awwwww, that definitely sounds like the “love bite” type. The solution is still the same – when she demands attention inappropriately, say “No bite!” and get up and walk away. Never reward her demands by giving her what she wants. Instead, when she is sitting with you calmly, or relaxing in a sunspot, you can go pet her then, so she will start to get the idea that when she bites, she doesn’t get attention, but when she’s relaxed, she does.

You might also want to cut those 30 minute sessions down for a while – sort of like an “affection diet” for the affection glutton! Give her 10 minutes of devoted attention, then get up and leave. I realize she will probably follow you and ask for more petting… but again, just wait until she is relaxed, and then do 10 more minutes.

Cats that want affection can be stubborn… my other cat, Ming Sushi, INSISTS on sitting on my lap at a certain time each evening. If my laptop is occupying that space, she will drape herself over my arms (fingers still typing away) and across my chest. All typos you see from me are, naturally, HER fault!`,
					children: [{
						author: 'Marissa',
						date: '2011-09-06T15:15-07:00',
						body: `that makes complete sense, she is SUCH a lover, she has to have attention all the time and we love to oblige her just not when she bites LOL it doesn’t hurt, but if she were to nip the girls it might scare them and maybe hurt a little, we don’t want them to be afraid of kitty, she love her so much =D thanks for the advice!!`,
						children: []
					}]
				}]
			},
			{
				author: 'Deanna R. Jones',
				date: '2015-03-24T11:15-07:00',
				body: `I used to have a similar problem with my cat. She really was a sweetheart, but she would sometimes bite when I would play with her or pet her. I think it was because she didn’t really know better. Eventually, she learned not to bite people when she gets excited. I had to teach her to avoid biting by showing her that her biting hurt me so that she would know that I didn’t like what she was doing. She was a young kitten at the time, so it was really easy to train her to be gentle with people. What would you recommend for teaching an older cat to avoid biting? The petting sessions technique in the article seems to work for younger cats, but it would be helpful to know whether lengthening the petting sessions would be effective for older cats.`,
				children: []
			}
		]
	},
	{
		path: 'bringing-home-your-new-cat',
		title: 'Bringing Home Your New Cat',
		body: `{{>aside i='1' caption='Welcome home'}}One key to making successful shelter cat placements is managing the expectations of their new people. Here is some information, gleaned from various websites and the personal experiences of our adopters and volunteers, which we like to share with each cat adopter before the day they bring their new family member home. Have other ideas to help make the transition easier? Send us a comment below! You can never have too much good advice.

## Welcome home!

Finding the cat of your dreams may have been easy, but fitting a new feline friend into your household usually requires a little patience and time.

To make your cat’s transition as comfortable as possible, we recommend placing her in a quiet, closed-in area such as a bedroom or a small room away from the main foot traffic and other pets, providing food, water, and a litterbox.  Let your new pet get used to that one area for the first few days, sniffing your belongings and finding hiding places.  Make frequent visits to play with, feed, pet, and interact with your new cat.  Then you can begin slowly introducing her to the rest of your house, including the other pets.  We recommend keeping cats indoors at all times for their health and safety, and that of the birds and other outdoor animals, BUT if you decide you want your cat to be an indoor/outdoor cat, do NOT let her go outside for at least two weeks after bringing her home, and then begin with short, supervised visits to the yard, so your cat can get her bearings and recognize her home turf.  Always put a collar with ID information on a cat who goes outside.

### INTRODUCING A NEW CAT TO A RESIDENT CAT

{{>aside i='2' caption='Cat Buddies'}}

Your resident cat will sense this “intruder” in his home; be sure to spend extra time with your resident cat, to help relieve anxiety and tension. Place your resident cat’s food dish near the door to the room where the new cat is confined.  Gradually move the confined cat’s food closer to the inside of the door.  Feed them at the same time so they are separated by only the closed door.  Some growling and hissing is to be expected – this is NORMAL and does not mean the cats will never tolerate each other!

When neither cat growls, hisses, or spits, you are ready to move on to the next step: confine your resident cat, with its own food, water, and litter box, to a location with which he is comfortable.  Allow the new cat to explore your home for brief periods, accompanying it to give it the comfort of your presence as it explores.  Then return the new cat to her “safe room” and let your resident cat out of its room.

It is a good idea to rub each cat with a cloth to transfer its scent to the cloth, then place the cloth with the other cat so they can get used to each other’s scents.

After several days of this, you may be ready to let both cats roam in your house for the first time; try to plan this for mealtime.  Feed the cats in each other’s presence, placing their food dishes a comfortable distance apart.  Some hissing and hesitation are to be expected; hopefully the food will distract them from each other.  Wait only a few minutes after eating to return the new cat to its “safe room.”  If either cat is too disturbed to eat in the other’s presence, return the new cat to its “safe room” and try again the next day.  You can gradually move their food dishes closer to each other; allow the cats to spend progressively longer periods together after they have eaten.  It is advisable to maintain one litter box for each cat, although when they have accepted each other, each cat will likely use both boxes.  Be patient; most cats learn to accept each other over time.

Some anxiety in the resident cat can be alleviated by ignoring the new cat in the resident cat’s presence, and referring to the new cat as “resident-cat’s-name’s friend, new-cat’s-name” (example: “This is Sneakers’ friend Pumpkin, Sneakers will love to play with Pumpkin…”). Sounds weird, but it works!

### INTRODUCING A NEW CAT TO A RESIDENT DOG

{{>aside i='3' caption='Cat + Dog'}}

Be sure the dog is restrained on a firmly held short leash and the cat is free to escape; do not allow the cat to come within the dog’s biting range.  if your dog guards its food, the cat may risk injury if it approaches the dog’s food, and it may be necessary to move the dog’s food or confine the cat.  If the dog acts aggressively toward the cat, use corrective behavior techniques with the dog.  Dogs can usually be trained to ignore, or even play with, cats.

### COMMON ISSUES WITH A NEW CAT OR KITTEN

#### My kitten doesn’t use his litterbox!

Sometimes cats and kittens are a bit overwhelmed when going to their new home; if they are allowed to roam in too large a space, they may take some time to learn where the litterbox is, and this can lead to accidents.  If you confine your new cat to one room at first, you can then gradually move his litterbox out of that room and into your preferred location, by moving it a few feet per day. The cat will most likely follow the box!  If you witness your kitten missing the box, you can place the kitten into the box and move its paw in a scratching motion – this helps the kitten associate the box with a place it can bury its waste.

#### My cat uses his litterbox, but sometimes she prefers to use my houseplants!

Fresh dirt can be irresistible to some cats looking for a burial spot; to discourage this behavior, cover the soil surface with pinecones – big and spiny are best.

#### My cat has runny stools!

This is an extremely common situation for cats and kittens moving to a new home; cats have very sensitive digestive systems, and just the stress of a big change can be enough to bring on diarrhea. Changes in diet that occur in a new home can also contribute to this problem.  It will usually clear up on its own in a few days; be sure the cat has enough water and easy access to the litterbox. If the problem persists or worsens, consult your veterinarian – although shelters do their best to prevent and eradicate diseases among cats, there are a number of minor things that can cause diarrhea; most are easily treated once identified by a stool sample.

#### My kitten is sneezing and has runny eyes and nose!

One of the most common ailments to strike cats and kittens right after moving to a new home is the upper respiratory infection, or URI. Again, stress is the culprit; kittens that showed no symptoms prior to being placed in the carrier for transport, can begin showing symptoms in a matter of hours after arriving in the vast, scary, unknown world of your house.  Usually, the kitten’s immune system will fight off the URI in just a few days without any veterinary intervention. The closest human example is the common cold; when we are stressed, we are more susceptible to infection by the virus, but generally fight it off quickly with just a bit of chicken soup. (Yes, go ahead and give chicken soup to the kitten if it makes you feel better). If your kitten is not listless or dehydrated, and not running a very high fever, there really won’t be much else you can do to help them get over the “cold” besides cuddling and petting them (OK, OK, and chicken soup).  If, however, your kitten’s nose or eyes begin secreting yellowish or greenish mucous, or the third eyelid begins showing prominently, or your kitten is extremely listless, he may be suffering from a secondary bacterial infection – those CAN be treated with a course of easily administered antibiotics, so definitely take him to see the doctor, and “supercharge” that chicken soup.

#### My kitten is shredding my couch!

Inappropriate scratching CAN be cured. The first thing is to provide several appropriate scratching places so your kitten can choose the material it likes; sisal posts, corrugated cardboard pads, even logs have been used with success.  Then, to discourage the unwanted scratching, wrap the chosen item in tape, sticky side out – cats DESPISE this feeling on their paws.

#### My cat won’t come out from under the bed!

She is undoubtedly frightened and disoriented by being in a new place; use this room as her “safe room” for the first few days, and go in and talk quietly to her, read her a book, play music for her – but DON’T force her to come out.  She wants to be friends – she’s just not sure what to expect. Take your time and show her you’re not a big, scary monster, and that she now belongs with you – no more cages or uncertainty!  Cats can be a challenge, but a little patience works wonders.

Here’s a good article on cat communication: https://www.wikihow.com/Communicate-with-Your-Cat`,
		author: 'Kim',
		date: '2011-05-25T19:33-07:00',
		photos: [
			{
				path: 'blog/wp-content/uploads/2011/05/GeorgeShannon-150x112.jpg',
				sizes: []
			},
			{
				path: 'blog/wp-content/uploads/2011/05/Blinken0719-2.jpg',
				sizes: []
			},
			{
				path: 'blog/wp-content/uploads/2011/05/Ecgtheow-Spudnik-2005-300x225.jpg',
				sizes: []
			},
			{
				path: 'blog/wp-content/uploads/2011/05/Booduh-Moo-06-29-07-280x300.jpg',
				sizes: []
			}
		],
		comments: [
			{
				author: 'Angela Matesky Ertel',
				date: '2023-09-05T11:09-07:00',
				body: 'Would you suggest using a cat pheromone defuser?',
				children: [
					{
						author: 'Kim - Forget Me Not',
						date: '2024-03-20T11:46-07:00',
						body: `In cases where the normal “hissy-spitty” introduction period doesn’t seem to be improving, a pheromone diffuser can be a helpful option. Not all cats are affected by pheromone diffusers, but it’s common enough to be worth a try. We do use them at the shelter.`,
						children: []
					}
				]
			}
		]
	},
	{
		path: 'bringing-home-your-new-dog',
		title: 'Bringing Home Your New Dog',
		body: `{{>aside i='1' caption='Yay, a new toy!'}}One key to making successful shelter dog placements is managing the expectations of their new people. Here is some information, gleaned from various websites and the personal experiences of our adopters and volunteers, which we like to share with each dog adopter before the day they bring their new family member home. Have other ideas to help make the transition easier? Send us a comment below! You can never have too much good advice.

## Welcome home!

Finding the dog of your dreams may have been easy, but fitting a new canine friend into your household usually requires a little patience and time.

To make your dog’s transition as comfortable as possible, we recommend keeping your new dog on a leash or at your side at first.  Show him where his water and food dish are kept.  Show him where he is to sleep.  When he is indoors be sure to keep him confined or with you, taking him outdoors at frequent intervals to relieve himself.   Take him to the same spot each time and praise him heartily when he goes.   Until he learns this new routine he will have to be watched closely.  If there is an accident in the house please do not assume he is not housebroken.  He must get accustomed to his new home and his new routines.  However, if you catch him in the act of eliminating in the house, loudly say “NO!” and take him outside immediately.  NEVER hit your dog if an accident occurs.   Praise, not punishment, is the key to a well behaved pet.

The first couple of weeks you and your pet are “getting to know one another”; he doesn’t know why he has come to your home, nor what is expected of him.   Please be patient with him and anticipate problems before they occur.  Don’t leave tempting shoes, clothing, or children’s toys within reach of your dog.

When he’s first settling in, your dog may experience shyness, anxiety, restlessness, excitement, crying or barking.  He may exhibit excessive water drinking, frequent urination, or diarrhea.  His appetite may not be good.  It is best to continue feeding the food your dog is used to from the shelter, and to slowly mix that food in with the food you and your veterinarian have chosen for your dog’s new regular diet.  Until your dog is settled, try to give bits of his regular kibble as treats, rather than introducing new forms of treats that may upset your dog’s digestive system; new treats may be added slowly after the dog has settled into his new routine in your home.  If your dog exhibits any medical symptoms that last more than a few days, take him to your veterinarian for evaluation.

Your new dog must learn a whole set of new rules.  Be patient and be consistent. If you want him off the furniture, don’t allow him to sit on the couch “sometimes”.  Don’t allow him to do something one time and forbid it another.  There are numerous resources for training tips and methods, online and at your library. If you have access to obedience classes, they can be a great way to bond with your new dog and teach him what you want him to do – dogs just want to please you!

### INTRODUCING A NEW DOG TO A RESIDENT DOG

{{>aside i='2' caption='Meeting the Big Dog'}}

Introduce the dogs in a neutral location so that your resident dog is less likely to view the newcomer as a territorial intruder. Each dog should be handled by a separate person. With both dogs on a leash, take them to an area with which neither is familiar, such as a park or a neighbor’s yard.  From the first meeting, you want both dogs to expect “good things” to happen when they’re in each other’s presence. Let them sniff each other, which is normal canine greeting behavior. As they do, talk to them in a happy, friendly tone of voice – never use a threatening tone of voice. Don’t allow them to investigate and sniff each other for a prolonged time, as this may escalate to an aggressive response. After a short time, get both dogs’ attention, and give each dog a treat in return for obeying a simple command, such as “sit” or “stay.” Take the dogs for a walk and let them sniff and investigate each other at intervals. Continue with the “happy talk,” food rewards and simple commands.  Watch carefully for body postures that indicate an aggressive response, including hair standing up on the other dog’s back, teeth-baring, deep growls, a stiff legged gait or a prolonged stare. If you see such postures, interrupt the interaction immediately by calmly and positively getting each dog interested in something else.

Puppies usually pester adult dogs unmercifully. Before the age of four months, puppies may not recognize subtle body postures from adult dogs signaling that they’ve had enough. Well-socialized adult dogs with good temperaments may set limits with puppies with a growl or snarl. These behaviors are normal and should be allowed. Adult dogs that aren’t well-socialized, or that have a history of fighting with other dogs, may attempt to set limits with more aggressive behaviors, such as biting, which could harm the puppy. For this reason, a puppy shouldn’t be left alone with an adult dog until you’re confident the puppy isn’t in any danger. Be sure to give the adult dog some quiet time away from the puppy, and perhaps, some individual attention as described above. (Humane Society of the United States, 2009)

### INTRODUCING A NEW DOG TO A RESIDENT CAT

{{>asaide i='3' caption='Fast Friends'}}

Be sure the dog is restrained on a firmly held short leash and the cat is free to escape; do not allow the cat to come within the dog’s biting range.  If your dog guards its food, the cat may risk injury if it approaches the dog’s food, and it may be necessary to move the dog’s food or confine the cat.  If the dog acts aggressively toward the cat, use corrective behavior techniques with the dog.  Dogs can usually be trained to ignore, or even play with, cats. When you leave the house, separate the animals in physically, securely separated areas. Give each access to water, a bed or other suitable resting place, and some toys. Be sure the cat has access to a litter box. For the cat’s safety, make sure the cat has escape routes to get away from the dog. For example, a cat door leading to another room in the house and ledges on which he can easily jump. Always provide places where each animal can retreat for safety and privacy, a spot that is his or hers alone. A cat can use the top of the refrigerator; a dog can use a crate. (Fry, 2005)

### HOUSETRAINING TIPS:

Consistency is the key. One well-received training method involves three different types of confinement.

1. When you will be at home, keep the puppy on a leash at your side at all times. Take him outside each hour, to your chosen “bathroom” area, and stay there for 5-10 minutes. If your pup goes, praise him enthusiastically and reward him with a treat. If he doesn’t go, just bring him back inside and keep him on the leash next to you; repeat the process in another hour.

2. When you will be away for short periods (1-2 hours for very young pups, 3-6 hours for older pups), confine the pup to his comfy crate with his favorite cuddly toy and a treat; make the crate a fun place for him, but make sure it isn’t large enough for him to choose one corner as his bathroom! He needs just enough room to stand up, turn around, lie down, and have small food & water dishes available. He will try VERY VERY HARD to not soil his crate if it is his “bedroom” and “kitchen” and doesn’t have room to be the “bathroom” too.  Be sure to let the pup outside immediately when you return, and praise him and reward him when he goes (particularly if he tries to make it all the way to the designated spot in the yard).

3. When you must be away for a longer period, it is unrealistic to expect a young pup to “hold it” for 5, 8, or 10 hours.  Instead, you must find a place where the pup can be confined to a cleanable area; many people use exercise pens on a bathroom, laundry room, kitchen or garage floor. Inside the pen, place newspapers across most of the floor space; put the puppy’s crate inside the pen, propped open, with a comfort toy and a treat, and put food and water dishes close to the crate; you have now created the bedroom, kitchen, and bathroom areas for the pup.  As your pup gets better at this, gradually reduce the amount of floor that is covered in newspaper… your pup will eventually become so good at going only on the paper, that you can leave just one width of newspaper down and the pup will go there every time. (NOTE: adult dogs trained in this method can reliably be expected to seek out any newspaper they can find if they are left at home alone for a little too long; this can save your slippers in the long run).

*If this method doesn’t work for you, there are many websites and books dedicated to different training methods – keep trying until you find one that works for you AND your new companion! Here’s a great web article: https://www.wikihow.com/Communicate-With-Your-Dog*
 

Enjoy your new friend – here’s hoping you have many happy years to enjoy each other!`,
		author: 'Kim',
		date: '2011-05-17T20:41-07:00',
		photos: [{
			path: 'blog/wp-content/uploads/2011/05/LighteningFrost-144x150.jpg',
			sizes: []
		}, {
			path: 'blog/wp-content/uploads/2011/05/Turtle4.jpg',
			sizes: []
		}, {
			path: 'blog/wp-content/uploads/2011/05/Shepp121007.jpg',
			sizes: []
		}, {
			path: 'blog/wp-content/uploads/2011/05/Moo-Ecgtheow-Booduh-01-06-e1305689580244-1024x615.jpg',
			sizes: []
		}],
		comments: [{
			author: 'Mary',
			date: '2011-05-18T16:43-07:00',
			body: `I’ve tried. I just can’t seem to come up with anything else…it’s covered in this blog. My two big ones (covered in this blog) is never punish with a crate, and if there’s a change in the dog, rule out a medical issue before trying anything else…`,
			children: [
				{
					author: 'fmnas',
					date: '2011-05-19T10:02-07:00',
					body: `Both excellent and important points!

A crate is a “den” or “safe haven” for your dog; for your dog to embrace the safety of his crate, it’s important that it always be associated with GOOD things. Even without thinking of it as punishment, if the only time your dog is ever crated is during a rushed 2 minutes before you disappear from the house without him, he will develop negative associations with his crate, and will learn to dread crate time.

If, however, he knows he can often find yummie “cookies” or a special bone or toy when he goes to his crate, even when the door is open, you will find him learning to love his crate; he may even choose to snooze in there on his own, when the door is open while you are home! Just like people, dogs enjoy a little “me” time and space.

Medical issues are also important to note! While most rescues have their dogs in foster care, so their 24-hour habits and behaviors can be observed closely, shelters have a more difficult time identifying some elusive medical issues that may not be readily apparent during the scheduled times staff or volunteers are working with the dog. Forget Me Not just recently place a wonderful dog, a year+ golden retriever mix, who is on his way back to us because, after arriving at his home, his adopters discovered he suffers from what appears to be urinary incontinence (not to be confused with a lack of housetraining, UI is a condition where the dog urinates involuntarily, usually while resting or sleeping). While shelters disclose every condition that is known, and treat for them prior to adoption, there can always be medical issues that were not readily apparent at the shelter, or that arise within the first days or weeks at the new home. If your dog may be having a medical issue, definitely consult with your veterinarian; most shelters and rescues will take back any dog that arrives with a previously undiagnosed medical issue if it’s something with which you do not want to deal.`,
					children: []
				}
			]
		}]
	},
	{
		path: 'stop-the-cycle',
		title: 'Stop the Cycle',
		body: `{{>figure i='1' caption='Stop the Cycle information'}}
		
Do you know a Ferry County resident who is littering? I don’t mean tossing fast food wrappers out of their car; I mean creating unwanted litters of puppies or kittens, because they have been unable to spay their female pets.

Forget Me Not has a fabulous program to help end the “littering” problem in Ferry County. Our Stop the Cycle program gives residents with an “oops” litter a way out. We will take in the entire litter of puppies or kittens after they are weaned, and will make sure each is spayed/neutered before going to a screened and approved adoptive home. There is no surrender fee for Stop the Cycle litters; the only requirement is that the owner allow us to spay the mother animal, at little or no cost to them.

Please help us to encourage Ferry County “litterers” to clean up right now, by spaying all their female pets and allowing Forget Me Not to place the puppies or kittens into new homes after spay/neuter. No unwanted litter (yet)? We also offer voucher assistance to low-income county residents; while we enjoy finding new homes for unwanted litters, it’s always best to prevent that litter in the first place.

One simple phone call can Stop the Cycle.`,
		author: 'Kim',
		date: '2011-04-07T12:28-07:00',
		photos: [{
			path: 'blog/wp-content/uploads/2011/04/SydAd-130x150.jpg',
			sizes: []
		}, {
			path: 'blog/wp-content/uploads/2011/04/Stop-the-Cycle-handout-flyers.jpg',
			sizes: []
		}],
		comments: []
	},
	{
		path: 'big-black-dogs',
		title: 'The Big Black Dog Dilemma, featuring Teague',
		body: `People are often amazed to learn that there are many factors that can hinder even the friendliest dog’s chance of adoption.

Meet Teague, who has been waiting for a new forever home for two months. Teague is a perfect dog to illustrate many of the “less-desirable” qualities that keep him waiting while he sees newer arrivals joyously celebrating their adoptions all around him.

{{>figure i='1'}}

OK, now that you’ve seen Teague, see how many attributes you can think of that are keeping him languishing in the shelter day after day. Go ahead, scribble a list.

Some you can see; some you can’t. Make some wild guesses!

Done? OK, here is a breakdown of Teague’s particular problems:

1. **AGE** – Teague came in as a stray, so there is no way for us to know exactly how old he is. Based on his grey muzzle and his teeth, we are assuming he is at least 6 years old; he may be as old as 10, 12, ?? He still has the inner joy and playfulness of a puppy, but even the most die-hard dog lover is hesitant to adopt an older dog. Most pet lovers have felt the pain of losing a furry best friend in the past… it takes a very special person (one with a stronger tolerance for pain than I have) to be willing to open their heart up to a pet that is already as much as halfway through its lifespan.
2. **SIZE** – Teague is a bit over 60 pounds, which counts as a “large” dog. The most requested size for an adoptable dog is under 20 pounds – that is because many housing associations, condominiums, and apartments have firm size restrictions. The next most popular size is generally up to 40-50 pounds; people like knowing that they could pick their pet up if they had to.  Then there is a HUGE jump (no pun intended) to people looking for 100-pound-plus dogs; the Mastiffs, Great Danes and Irish Wolfhounds of the world have some really dedicated fans! That leaves Teague in the least popular weight category, 60-100 pounds.
3. **COLOR** – Black. Black, black, black. Yes, black is beautiful, but it is also the last color pup chosen from a litter… the last adult dog to be oooohed and aaaaaahed over in the shelter kennels. Some people think black dogs look “mean” – others just think they aren’t as pretty as the more “colorful” varieties. Because black is a dominant color gene, black dogs are also the most common color (particularly in “mutts”), so there are just plain too many of them looking for homes. They are also cursed with a general lack of photogenic charm (though our latest volunteer photographer, Lance Young, has found the art of capturing black dogs on camera).
4. **BREED MIX** – Notice that dusky purplish tongue? Purple tongues usually indicate that a dog like Teague has some Chow in his mix. Chows often suffer from breed-banning-overkill, along with several other “dangerous” dog breeds including Rottweilers, German Shepherds, Dobermans, and Pit Bulls. Think that covers the banned breeds? Not even close. Some municipalities, homeowners insurance, and housing authorities routinely ban perennial favorites like Australian Shepherds, Airedale Terriers, Golden Retrievers, Pugs, and more. See more of the breeds that have been singled out in legislation around the US on [the URDOG site](https://www.povn.com/urdog/banned%20breeds.html). Of course, a dog’s breed doesn’t make a dog dangerous. Bad breeding and bad treatment make a dog dangerous.
5. **GENDER** – More adopters express a preference for female dogs, often because they are afraid a male dog will mark, or wander, or be more aggressive than a female dog. Often, when an adopter who originally wanted a female ends up falling in love with a male, they tell me how surprised they are at their new male buddy’s affectionate, easy-going nature.
6. **INCOMPATIBLE WITH SOME OTHER PETS** – In Teague’s case, he is cat aggressive and cannot be placed in a home with cats. (The flip-side is, he would be a fantastic rat-catcher-dog!) Teague should get along well with most other dogs, but a majority of potential adopters looking to add to their family either already have cats, plan to get a cat, or have neighbors whose cats sometimes wander across the property.

So, there you have a picture of one of the friendliest, most loving, easy-keeper dogs I’ve seen this year… and still he waits for someone to choose him.

What can we do to help Teague find his new family? The most important thing is to pass the word around, that this beautiful, affectionate, playful dog needs a home where he can be loved for the rest of his time, whether that is 1 year or 10 years.

Watch his videos on [his listing page on our website](https://forgetmenotshelter.org/dogs/0630Teague) to see his playful nature. Share his information with everyone you can think of who might be willing to offer Teague a place to enjoy life. Together, we can make a happy ending for Teague.

Teague is growing depressed in the shelter, and would enjoy a cat-free foster home in the Republic area, preferably with a fenced yard, while he waits for his forever-home.
`,
		author: 'Kim',
		date: '2011-03-22T20:36-07:00',
		photos: [
			{
				path: 'blog/wp-content/uploads/2011/03/Teague315-3-143x150.jpg',
				sizes: []
			},
			{
				path: 'blog/wp-content/uploads/2011/03/Teague315-4.jpg',
				sizes: []
			}
		],
		comments: [
			{
				author: 'Mary',
				date: '2011-03-24T15:49-07:00',
				body: `As a member of the rescue community, I will concur with the comments that size, sex and color are the biggest hindrance to adoption. Black dogs get a bad rap. Male dogs get a bad rap. Medium – to – large (the 60-100 lb) dogs get … well, not much notice at all.

Teague is beautiful – more hair than I like (hey, I like short haired dogs!) but looks like he’d make a perfect pet. I’m going to cross post his page. Maybe someone I know will know someone who knows someone in Seattle, Spokane or…wait, you’re the only person I know in Malo/Curlew. ; )`,
				children: []
			},
			{
				author: 'Fran',
				date: '2011-03-27T00:02-07:00',
				body: `Contact olddoghaven.org

Teague could possibly be placed in an interim home through them.`,
				children: [
					{
						author: 'fmnas',
						date: '2011-03-27T12:37-07:00',
						body: `That’s a good idea; I know their foster homes are usually full, but definitely worth a try! We love Old Dog Haven (in fact, our treasurer adopted her current dog from them). People willing to give an old dog a second chance are the BEST!`,
						children: []
					}
				]
			},
			{
				author: 'Marta',
				date: '2011-03-27T14:08-07:00',
				body: `I love my time with Teague, thinking maybe a city home where they don’t have cats. Around here that isn’t so common but lots of people in the cities don’t have cats for whatever reason. I see how depressed he is and it breaks my heart every week.
Gretchen and I are hoping that he finds someone for keeps really soon.`, children: []
			}
		]
	},
	{
		path: 'underweight',
		title: 'Underweight Pets',
		body: `{{>figure i='0' caption='Underweight dog'}}If your pet looks like this, make sure that you’re feeding them enough; check the package on their food and make sure that you’re meeting or exceeding the feeding recommendations. If you are, check with your veterinarian: weight loss can be caused by parasites, thyroid disorders, diabetes, and other serious but treatable conditions.

Since her arrival at the shelter, [Oatmeal](https://forgetmenotshelter.org/Dogs/0614Oatmeal/) has filled out nicely and has been adopted!

{{>figure i='1' caption='Oatmeal on January 23, 2011, after gaining weight from proper feeding at the shelter.'}}`,
		author: 'Kim',
		date: '2011-01-30T14:32-08:00',
		photos: [{
			path: 'dogs/0614Oatmeal/Oatmealintake.jpg',
			sizes: [
				{
					path: 'blog/wp-content/uploads/2011/01/Oatmealintake-150x84.jpg',
					scale: 0.28
				}
			]
		}],
		comments: []
	}
];


export const migratePosts: HttpFunction = async (req, res) => {
	logger.debug('migratePosts', req.body);

	const { database } = req.body as {
		database?: string,
	};
	if (!database) {
		res.status(400).send('Incomplete request object\n');
		return;
	}
	logger.debug('connecting to firestore');
	const firestore = new Firestore({
		databaseId: database,
		ignoreUndefinedProperties: true
	});

	await firestore.recursiveDelete(firestore.collection('blog'));
	for (const post of posts) {
		await firestore.collection('blog').add(post);
	}

	res.send('ok\n');
};
