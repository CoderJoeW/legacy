import sys
import MySQLdb
import string
import random

user = sys.argv[1]
hashedPass = sys.argv[2]
email = sys.argv[3]
gameIP = sys.argv[4]
externalID = sys.argv[5]

if int(externalID) > 0:

	social_network = sys.argv[6]

def pwd_generator(size=8, chars=string.ascii_uppercase + string.digits + string.ascii_lowercase):
	return ''.join(random.choice(chars) for x in range(size))

cur = False

#Lembrando que as tabelas tem que ser INNODB

try:

	db = MySQLdb.connect(host="localhost",user="he",passwd="supersecurepass",db="HackerExperience")
	cur = db.cursor()

	
	
	
	

	if int(externalID) > 0:

		if social_network == 'facebook':

			cur.execute("	INSERT INTO users_facebook \
								(userID, gameID) \
							VALUES \
								("+externalID+", "+userID+") \
						")

		elif social_network == 'twitter':

			cur.execute("	INSERT INTO users_twitter \
								(userID, gameID) \
							VALUES \
								("+externalID+", "+userID+") \
						")


	db.commit()

except:

	if cur:
		print "LOG-ME: Rolling back create_user "+user+" using "+email
		db.rollback()

finally:

	if cur:
		db.close()

		os.system('python /var/www/python/profile_generator.py '+str(userID))
		os.system('python /var/www/python/profile_generator.py '+str(userID)+' br')

